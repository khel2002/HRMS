<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

  // ── PATCH /admin/api/leave-requests/{id}/remark ───────────────────────────
  //
  // General-purpose remark setter used by the Change Remark dropdown.
  //
  // Body:
  //   remark — pending | approved | rejected  (required)
  //   reason — optional HR comment (only stored when remark = rejected)

  public function setRemark(int $id, Request $request): JsonResponse
  {
    $request->validate([
      'remark' => ['required', 'in:pending,approved,rejected'],
      'reason' => ['nullable', 'string', 'max:500'],
    ]);

    try {
      $la = LeaveApplication::findOrFail($id);

      $la->update([
        'remarks' => $request->input('remark'),
        // Only keep a reason when disapproving; clear it otherwise
        'reason'  => $request->input('remark') === 'rejected'
          ? ($request->input('reason') ? trim($request->input('reason')) : null)
          : null,
      ]);

      Log::info('LeaveApplication remark updated', [
        'id'     => $id,
        'remark' => $request->input('remark'),
        'by'     => Auth::id(),
      ]);

      $label = match ($request->input('remark')) {
        'pending'  => 'reset to pending',
        'approved' => 'approved',
        'rejected' => 'disapproved',
        default    => 'updated',
      };

      return response()->json(['message' => 'Leave application ' . $label . ' successfully.']);
    } catch (Throwable $e) {
      Log::error('LeaveSummary setRemark failed', ['id' => $id, 'error' => $e->getMessage()]);
      return response()->json(['message' => 'Failed to update. Please try again.'], 500);
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
