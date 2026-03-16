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
    // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * Return the authenticated user's employee_id.
   *
   * Tries three common patterns in order:
   *   1. $user->employee_id  (FK column directly on users table)
   *   2. $user->employee->id (belongsTo relationship)
   *   3. $user->id           (user IS the employee — same table)
   *
   * Returns null when unauthenticated so callers can handle it gracefully.
   */
  private function employeeId(): ?int
  {
    $user = Auth::user();

    if (! $user) {
      return null;
    }

    // Pattern 1 — direct FK column on users table
    if (isset($user->employee_id) && $user->employee_id) {
      return (int) $user->employee_id;
    }

    // Pattern 2 — belongsTo relationship
    if (method_exists($user, 'employee') && $user->employee) {
      return (int) $user->employee->id;
    }

    // Pattern 3 — user row IS the employee row
    return (int) $user->id;
  }

  // ── Web route ─────────────────────────────────────────────────────────────

  public function index()
  {
    return view('content.applications.leave-summary');
  }

  // ── API: GET /api/leave-requests ─────────────────────────────────────────
  // Query params:
  //   status  — pending | approved | disapproved | cancelled  (required)
  //   year    — 4-digit year, defaults to current year

  public function list(Request $request): JsonResponse
  {
    $request->validate([
      'status' => ['required', 'in:pending,approved,disapproved'],
      'year'   => ['nullable', 'integer', 'min:2000', 'max:2099'],
    ]);

    $year = (int) ($request->input('year') ?? now()->year);

    $employeeId = $this->employeeId();

    if (! $employeeId) {
      return response()->json(['message' => 'Unauthenticated or employee record not linked to your account.'], 401);
    }

    try {
      $rows = LeaveApplication::with('leaveType')
        ->forEmployee($employeeId)
        ->byStatus($request->status)
        ->byYear($year)
        ->orderByDesc('date_filed')
        ->get()
        ->map(fn($la) => [
          'id'             => $la->id,
          'leave_type'     => trim($la->leaveType->name ?? '—'),
          'date_of_filing' => $la->date_filed?->toDateString(),
          'total_days'     => $la->total_days,
          'status'         => $la->status,
          'remarks'        => $la->reason,
        ]);

      return response()->json(['data' => $rows]);
    } catch (Throwable $e) {
      Log::error('LeaveSummary list failed', ['error' => $e->getMessage()]);

      // Return the real message in non-production so you can debug without devtools
      $msg = app()->isProduction()
        ? 'Failed to load leave requests.'
        : $e->getMessage();

      return response()->json(['message' => $msg], 500);
    }
  }

  // ── API: GET /api/leave-requests/{id} ────────────────────────────────────
  // Returns full detail for the view modal (timeline + attachments).

  public function show(int $id): JsonResponse
  {
    try {
      $la = LeaveApplication::with(['employee', 'leaveType'])
        ->forEmployee($this->employeeId())
        ->findOrFail($id);

      return response()->json([
        'data' => [
          'id'             => $la->id,
          'employee_name'  => $la->employee
            ? trim($la->employee->first_name . ' ' . $la->employee->last_name)
            : Auth::user()->name ?? '—',
          'leave_type'     => trim($la->leaveType->name ?? '—'),
          'date_of_filing' => $la->date_filed?->toDateString(),
          'start_date'     => $la->start_date?->toDateString(),
          'end_date'       => $la->end_date?->toDateString(),
          'total_days'     => $la->total_days,
          'department'     => $la->department,
          'position'       => $la->position,
          'cause'          => $la->cause,
          'manager_status' => $la->manager_status,
          'status'         => $la->status,          // normalised via accessor
          'remarks'        => $la->reason,          // HR reason/comment
          'timeline'       => $la->timeline,
          'attachments'    => [],
          'file_url'       => null,
        ],
      ]);
    } catch (Throwable $e) {
      Log::error('LeaveSummary show failed', ['id' => $id, 'error' => $e->getMessage()]);

      return response()->json(
        ['message' => 'Leave request not found.'],
        404
      );
    }
  }

  // ── API: DELETE /api/leave-requests/{id} ─────────────────────────────────
  // Only the owner can delete, and only when status is 'pending'.

  public function destroy(int $id): JsonResponse
  {
    try {
      $la = LeaveApplication::forEmployee($this->employeeId())
        ->findOrFail($id);

      if ($la->status !== 'pending') {
        return response()->json(
          ['message' => 'Only pending requests can be deleted.'],
          422
        );
      }

      $la->delete();   // SoftDeletes — safe to restore if needed

      return response()->json(['message' => 'Leave request deleted successfully.']);
    } catch (Throwable $e) {
      Log::error('LeaveSummary destroy failed', ['id' => $id, 'error' => $e->getMessage()]);

      return response()->json(
        ['message' => 'Failed to delete leave request.'],
        500
      );
    }
  }
}
