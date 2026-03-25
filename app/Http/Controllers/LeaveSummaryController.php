<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Pdfs\LeaveApplicationPdf;
use Throwable;

class LeaveSummaryController extends Controller
{
  // ── Web route ─────────────────────────────────────────────────────────────

  public function index()
  {
    return view('content.applications.leave-summary');
  }

  // ── GET /admin/api/leave-requests ─────────────────────────────────────────
  // Query params: status (required), year (optional), employee_id (optional)

  public function list(Request $request): JsonResponse
  {
    $request->validate([
      'status'      => ['required', 'in:pending,approved,disapproved'],
      'year'        => ['nullable', 'integer', 'min:2000', 'max:2099'],
      'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
    ]);

    $year = (int) ($request->input('year') ?? now()->year);

    try {
      $query = LeaveApplication::with(['leaveType', 'employee'])
        ->byStatus($request->status)
        ->byYear($year)
        ->orderByDesc('date_filed');

      if ($request->filled('employee_id')) {
        $query->forEmployee((int) $request->input('employee_id'));
      }

      $rows = $query->get()->map(fn($la) => [
        'id'             => $la->id,
        'employee_name'  => $la->employee?->full_name ?? '—',
        'leave_type'     => trim($la->leaveType->name ?? '—'),
        'date_of_filing' => $la->date_filed?->toDateString(),
        'start_date'     => $la->start_date?->toDateString(),
        'end_date'       => $la->end_date?->toDateString(),
        'total_days'     => $la->total_days,
        'status'         => $la->status,
        'remarks'        => $la->reason,
      ]);

      return response()->json(['data' => $rows]);
    } catch (Throwable $e) {
      Log::error('LeaveSummary list failed', ['error' => $e->getMessage()]);
      return response()->json([
        'message' => app()->isProduction() ? 'Failed to load.' : $e->getMessage(),
      ], 500);
    }
  }

  // ── GET /admin/api/leave-requests/{id} ────────────────────────────────────

  public function show(int $id): JsonResponse
  {
    try {
      $la = LeaveApplication::with(['employee.position', 'employee.office', 'leaveType'])
        ->findOrFail($id);

      return response()->json([
        'data' => [
          'id'              => $la->id,
          'employee_name'   => $la->employee?->full_name ?? '—',
          'employee_number' => $la->employee?->employee_number ?? '—',
          'leave_type'      => trim($la->leaveType->name ?? '—'),
          'date_of_filing'  => $la->date_filed?->toDateString(),
          'start_date'      => $la->start_date?->toDateString(),
          'end_date'        => $la->end_date?->toDateString(),
          'total_days'      => $la->total_days,
          'office'          => $la->employee?->office?->office_name ?? '—',
          'position'        => $la->employee?->position?->position_name ?? '—',
          'cause'           => $la->cause,
          'manager_status'  => $la->manager_status,
          'status'          => $la->status,
          'remarks'         => $la->reason,
          'timeline'        => $la->timeline,
        ],
      ]);
    } catch (Throwable $e) {
      Log::error('LeaveSummary show failed', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => 'Leave request not found.'], 404);
    }
  }

  // ── GET /admin/leave-requests/{id}/pdf ───────────────────────────────────
  // Generates and streams the Leave Application PDF for a specific record.

  public function generatePdf(int $id)
  {
    try {
      $la = LeaveApplication::with([
        'employee.position',
        'employee.office',
        'leaveType',
      ])->findOrFail($id);

      $pdf = new LeaveApplicationPdf();

      return $pdf->generate([
        'name'           => $la->employee?->full_name           ?? '',
        'department'     => $la->employee?->office?->office_name ?? '',
        'position'       => $la->employee?->position?->position_name ?? '',
        'cause'          => $la->cause                          ?? '',
        'leave_type'     => $la->leaveType?->name               ?? '',
        'total_days'     => $la->total_days                     ?? '',
        'date_from'      => $la->start_date?->format('M d, Y')  ?? '',
        'date_to'        => $la->end_date?->format('M d, Y')    ?? '',
        'date_filed'     => $la->date_filed?->format('M d, Y')  ?? '',
        'manager_status' => $la->manager_status                 ?? '',
      ]);
    } catch (Throwable $e) {
      Log::error('Leave PDF generation failed', ['id' => $id, 'error' => $e->getMessage()]);
      abort(500, 'Could not generate PDF.');
    }
  }

  // ── PATCH /admin/api/leave-requests/{id}/remark ───────────────────────────
  //
  // General-purpose remark setter used by the Change Remark dropdown.
  //
  // Body:
  //   remark — pending | approved | rejected  (required)
  //   reason — optional HR comment (only stored when remark = rejected)
  //
  // BALANCE LOGIC:
  //   pending  → approved : DEDUCT  total_days from leave_balances
  //   approved → pending  : REVERSE the deduction (add days back)
  //   approved → rejected : REVERSE the deduction (add days back)
  //   pending  → rejected : no balance change (was never deducted)
  //   rejected → approved : DEDUCT  total_days from leave_balances
  //
  // Default allocations used when no leave_balances row exists yet:
  //   Vacation / SIL  — 5 days
  //   Sick Leave      — 5 days (can carry over up to 14 total)

  public function setRemark(int $id, Request $request): JsonResponse
  {
    $request->validate([
      'remark' => ['required', 'in:pending,approved,rejected'],
      'reason' => ['nullable', 'string', 'max:500'],
    ]);

    try {
      // Load with leaveType so we have leave_type_id and the type name for defaults
      $la = LeaveApplication::with('leaveType')->findOrFail($id);

      $previousRemark = $la->remarks; // capture BEFORE update
      $newRemark      = $request->input('remark');

      // ── Determine what balance action is needed ────────────────────────
      $wasApproved = ($previousRemark === 'approved');
      $nowApproved = ($newRemark       === 'approved');

      $shouldDeduct  = !$wasApproved && $nowApproved;  // becoming approved
      $shouldReverse = $wasApproved  && !$nowApproved; // un-approving

      DB::transaction(function () use ($la, $newRemark, $request, $shouldDeduct, $shouldReverse) {

        // 1. Update the leave application remark
        $la->update([
          'remarks' => $newRemark,
          'reason'  => $newRemark === 'rejected'
            ? ($request->input('reason') ? trim($request->input('reason')) : null)
            : null,
        ]);

        // 2. Adjust leave balance if needed
        if ($shouldDeduct || $shouldReverse) {
          $this->adjustLeaveBalance($la, $shouldDeduct);
        }
      });

      Log::info('LeaveApplication remark updated', [
        'id'       => $id,
        'previous' => $previousRemark,
        'new'      => $newRemark,
        'balance'  => $shouldDeduct ? 'deducted' : ($shouldReverse ? 'reversed' : 'unchanged'),
        'by'       => Auth::id(),
      ]);

      $label = match ($newRemark) {
        'pending'  => 'reset to pending',
        'approved' => 'approved',
        'rejected' => 'disapproved',
        default    => 'updated',
      };

      return response()->json(['message' => 'Leave application ' . $label . ' successfully.']);
    } catch (\RuntimeException $e) {
      // Thrown by LeaveBalance::deduct() when balance is insufficient
      Log::warning('LeaveSummary setRemark: insufficient balance', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
      Log::error('LeaveSummary setRemark failed', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => 'Failed to update. Please try again.'], 500);
    }
  }

  // ── Private: adjust leave balance on approval / un-approval ──────────────
  //
  // $deduct = true  → subtract total_days (approval)
  // $deduct = false → add total_days back  (reversal)
  //
  // If no leave_balances row exists for this employee + type + year,
  // one is created with the default annual allocation before deducting.
  //
  // Default allocations (days per year):
  //   Sick Leave                — 5  (unused carries over, max 14)
  //   Vacation Leave            — 5  (resets annually)
  //   Service Incentive Leave   — 5  (must use within year)

  private function adjustLeaveBalance(LeaveApplication $la, bool $deduct): void
  {
    $year         = $la->date_filed?->year ?? now()->year;
    $leaveTypeId  = $la->leave_type_id;
    $employeeId   = $la->employee_id;
    $days         = (int) ceil($la->total_days);

    // Default total_days per leave type (used only when creating a missing row)
    $typeName = strtolower($la->leaveType?->name ?? '');
    $defaultTotal = match (true) {
      str_contains($typeName, 'sick')    => 5,
      str_contains($typeName, 'vacation') => 5,
      str_contains($typeName, 'service') => 5,   // SIL
      default                            => 5,
    };

    // Find or create the balance row for this employee + type + year
    $balance = LeaveBalance::firstOrCreate(
      [
        'employee_id'   => $employeeId,
        'leave_type_id' => $leaveTypeId,
        'year'          => $year,
      ],
      [
        // Only used when creating a NEW row
        'total_days'     => $defaultTotal,
        'used_days'      => 0,
        'remaining_days' => $defaultTotal,
      ]
    );

    if ($deduct) {
      // Throws RuntimeException if insufficient — caught in setRemark()
      $balance->deduct($days);
    } else {
      // Reverse: add days back, keep values sane
      $newUsed      = max(0, $balance->used_days      - $days);
      $newRemaining = min($balance->total_days, $balance->remaining_days + $days);

      // Enforce SL max-14 cap on the remaining side
      if (str_contains($typeName, 'sick')) {
        $newRemaining = min(14, $newRemaining);
      }

      $balance->update([
        'used_days'      => $newUsed,
        'remaining_days' => $newRemaining,
      ]);
    }
  }

  // ── DELETE /admin/api/leave-requests/{id} ─────────────────────────────────

  public function destroy(int $id): JsonResponse
  {
    try {
      LeaveApplication::findOrFail($id)->delete();
      return response()->json(['message' => 'Leave request deleted successfully.']);
    } catch (Throwable $e) {
      Log::error('LeaveSummary destroy failed', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => 'Failed to delete.'], 500);
    }
  }
}
