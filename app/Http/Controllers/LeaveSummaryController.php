<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
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
        'total_days'     => (float) $la->total_days,
        'duration_type'  => $la->duration_label,
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
          'total_days'      => (float) $la->total_days,
          'duration_type'   => $la->duration_label,
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

  // ── GET /admin/api/leave-requests/{id}/pdf ────────────────────────────────

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
        'name'           => $la->employee?->full_name               ?? '',
        'department'     => $la->employee?->office?->office_name    ?? '',
        'position'       => $la->employee?->position?->position_name ?? '',
        'cause'          => $la->cause                              ?? '',
        'leave_type'     => $la->leaveType?->name                   ?? '',
        'total_days'     => (float) $la->total_days,
        'date_from'      => $la->start_date?->format('M d, Y')      ?? '',
        'date_to'        => $la->end_date?->format('M d, Y')        ?? '',
        'date_filed'     => $la->date_filed?->format('M d, Y')      ?? '',
        'manager_status' => $la->manager_status                     ?? '',
      ]);
    } catch (Throwable $e) {
      Log::error('Leave PDF generation failed', ['id' => $id, 'error' => $e->getMessage()]);
      abort(500, 'Could not generate PDF.');
    }
  }

  // ── PATCH /admin/api/leave-requests/{id}/remark ───────────────────────────
  //
  // When remark changes TO 'approved':
  //   → Call LeaveBalance::resolveForYear() which either returns the existing
  //     row or creates one with correct carry-over applied (sick leave rolls
  //     over unused days from the previous year, capped at 14).
  //   → Then deduct total_days under a pessimistic lock.
  //
  // When remark changes FROM 'approved' TO something else (pending/rejected):
  //   → Restore the previously deducted days back to the balance row.

  public function setRemark(int $id, Request $request): JsonResponse
  {
    $request->validate([
      'remark' => ['required', 'in:pending,approved,rejected'],
      'reason' => ['nullable', 'string', 'max:500'],
    ]);

    $newRemark = $request->input('remark');

    try {
      // Read the raw DB value before any mutation so $previousRemark
      // is never affected by the update() call inside the closure.
      $la             = LeaveApplication::with('leaveType')->findOrFail($id);
      $previousRemark = $la->getRawOriginal('remarks') ?? $la->remarks;

      DB::transaction(function () use ($la, $newRemark, $request, $previousRemark) {

        // ── 1. Update the leave application ───────────────────────
        $la->update([
          'remarks' => $newRemark,
          'reason'  => $newRemark === 'rejected'
            ? ($request->input('reason') ? trim($request->input('reason')) : null)
            : null,
        ]);

        $year      = $la->date_filed ? (int) $la->date_filed->format('Y') : now()->year;
        $totalDays = (float) $la->total_days;

        // ── Case 1: Transitioning TO approved → deduct ────────────
        if ($newRemark === 'approved' && $previousRemark !== 'approved') {

          // resolveForYear() creates the row if missing, applying
          // the correct carry-over rule for this leave type.
          LeaveBalance::resolveForYear(
            $la->employee_id,
            $la->leave_type_id,
            $year
          );

          // Re-fetch with a pessimistic lock to prevent concurrent
          // approvals from double-deducting the same row.
          $balance = LeaveBalance::where('employee_id', $la->employee_id)
            ->where('leave_type_id', $la->leave_type_id)
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail();

          $balance->deduct($totalDays);

          Log::info('LeaveBalance deducted on approval', [
            'leave_application_id' => $la->id,
            'employee_id'          => $la->employee_id,
            'leave_type_id'        => $la->leave_type_id,
            'year'                 => $year,
            'deducted_days'        => $totalDays,
          ]);
        }

        // ── Case 2: Transitioning FROM approved → restore ─────────
        if ($previousRemark === 'approved' && $newRemark !== 'approved') {

          $balance = LeaveBalance::where('employee_id', $la->employee_id)
            ->where('leave_type_id', $la->leave_type_id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

          if ($balance) {
            DB::table('leave_balances')
              ->where('id', $balance->id)
              ->update([
                'used_days'      => DB::raw("GREATEST(0, used_days - {$totalDays})"),
                'remaining_days' => DB::raw("remaining_days + {$totalDays}"),
              ]);

            Log::info('LeaveBalance restored on un-approval', [
              'leave_application_id' => $la->id,
              'employee_id'          => $la->employee_id,
              'leave_type_id'        => $la->leave_type_id,
              'year'                 => $year,
              'restored_days'        => $totalDays,
            ]);
          }
        }
      });

      Log::info('LeaveApplication remark updated', [
        'id'       => $id,
        'previous' => $previousRemark,
        'new'      => $newRemark,
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
      // Thrown by LeaveBalance::deduct() when remaining balance is insufficient.
      return response()->json(['message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
      Log::error('LeaveSummary setRemark failed', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => 'Failed to update. Please try again.'], 500);
    }
  }

  // ── DELETE /admin/api/leave-requests/{id} ─────────────────────────────────

  public function destroy(int $id): JsonResponse
  {
    try {
      $la = LeaveApplication::findOrFail($id);

      // If the application was approved, restore the balance before deleting.
      if ($la->getRawOriginal('remarks') === 'approved') {
        $year      = $la->date_filed ? (int) $la->date_filed->format('Y') : now()->year;
        $totalDays = (float) $la->total_days;

        $balance = LeaveBalance::where('employee_id', $la->employee_id)
          ->where('leave_type_id', $la->leave_type_id)
          ->where('year', $year)
          ->first();

        if ($balance) {
          DB::table('leave_balances')
            ->where('id', $balance->id)
            ->update([
              'used_days'      => DB::raw("GREATEST(0, used_days - {$totalDays})"),
              'remaining_days' => DB::raw("remaining_days + {$totalDays}"),
            ]);
        }
      }

      $la->delete();

      return response()->json(['message' => 'Leave request deleted successfully.']);
    } catch (Throwable $e) {
      Log::error('LeaveSummary destroy failed', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => 'Failed to delete.'], 500);
    }
  }
}
