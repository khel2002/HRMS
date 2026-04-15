<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;


class AttendanceReportController extends Controller
{
  public function __construct(
    private readonly AttendanceReportService $service,
  ) {}

    // ── Page Entry Point ──────────────────────────────────────────────────────

  /**
   * Render the daily attendance report page.
   * Loads today's data, or the most recent Friday if today is a weekend.
   *
   * GET /admin/attendance-report
   */
  public function index(): View
  {
    $date  = $this->latestWorkday();
    $daily = $this->service->getDailyReport($date);

    return view('content.admin.attendance-reports.attendance-overview', [
      'initialDate' => $date->toDateString(),
      'summary'     => $daily['summary'],
      'dailyLate'   => $daily['late'],
      'dailyLeave'  => $daily['on_leave'],
    ]);
  }

    // ── AJAX Endpoint ─────────────────────────────────────────────────────────

  /**
   * Return daily attendance data as JSON for a given date.
   *
   * GET /admin/attendance-report/daily?date=YYYY-MM-DD
   *
   * Success (200):
   * {
   *   "success": true,
   *   "date":    "2026-04-07",
   *   "summary": { "total_employees": int, "present": int, "late": int, "on_leave": int },
   *   "late":    [ { "name", "pos", "dept", "time_in", "late_duration", "av" }, ... ],
   *   "on_leave":[ { "name", "pos", "dept", "leave_type", "leave_days", "av" }, ... ]
   * }
   *
   * Rejected (422):
   * {
   *   "success": false,
   *   "reason":  "future" | "weekend",
   *   "message": "Human-readable explanation."
   * }
   *
   * Server error (500):
   * {
   *   "success": false,
   *   "reason":  "server_error",
   *   "message": "..."
   * }
   */
  public function daily(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'date' => ['required', 'date_format:Y-m-d'],
    ]);

    $date  = Carbon::parse($validated['date'])->startOfDay();
    $check = $this->service->validateReportDate($date);

    if (!$check['allowed']) {
      return response()->json([
        'success' => false,
        'reason'  => $check['reason'],
        'message' => $check['message'],
      ], 422);
    }

    try {
      $daily = $this->service->getDailyReport($date);

      return response()->json([
        'success'  => true,
        'date'     => $date->toDateString(),
        'summary'  => $daily['summary'],
        'late'     => $daily['late'],
        'on_leave' => $daily['on_leave'],
      ]);
    } catch (\Throwable $e) {
      report($e);

      return response()->json([
        'success' => false,
        'reason'  => 'server_error',
        'message' => 'Failed to load attendance data. Please try again.',
      ], 500);
    }
  }

    // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * Returns today if it is a weekday, otherwise the most recent Friday.
   */
  private function latestWorkday(): Carbon
  {
    $today = Carbon::today();
    return $today->isWeekend()
      ? $today->previous(Carbon::FRIDAY)
      : $today;
  }
}
