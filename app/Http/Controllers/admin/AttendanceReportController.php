<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AttendanceReportService;
use App\Pdfs\AttendanceReportPdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
   * GET /HR/attendance-report
   */
  public function index(): View
  {
    $date  = $this->latestWorkday();
    $daily = $this->service->getDailyReport($date);

    return view('content.admin.attendance-reports.attendance-overview', [
      'initialDate'  => $date->toDateString(),
      'summary'      => $daily['summary'],
      'dailyLate'    => $daily['late'],
      'dailyLeave'   => $daily['on_leave'],
      'dailyAbsent'  => $daily['absent'],
      'dailyPending' => $daily['pending'],
      'cutoffPassed' => $daily['cutoff_passed'],
      'scheduleInfo' => [
        'work_start'      => AttendanceReportService::WORK_START,
        'morning_cutoff'  => AttendanceReportService::MORNING_CUTOFF,
        'afternoon_start' => AttendanceReportService::AFTERNOON_START,
        'work_end'        => AttendanceReportService::WORK_END,
      ],
    ]);
  }

  // ── AJAX Endpoint ─────────────────────────────────────────────────────────

  /**
   * Return daily attendance data as JSON for a given date.
   *
   * GET /admin/attendance-report/daily?date=YYYY-MM-DD
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
        'success'       => true,
        'date'          => $date->toDateString(),
        'is_today'      => $daily['is_today'],
        'cutoff_passed' => $daily['cutoff_passed'],
        'summary'       => $daily['summary'],
        'late'          => $daily['late'],
        'on_leave'      => $daily['on_leave'],
        'absent'        => $daily['absent'],
        'pending'       => $daily['pending'],
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

  // ── PDF Export ────────────────────────────────────────────────────────────

  /**
   * Stream the daily attendance report as a PDF using TCPDF.
   *
   * GET /admin/attendance-report/pdf?date=YYYY-MM-DD
   * GET /HR/attendance-report/pdf?date=YYYY-MM-DD
   *
   * Date is optional — defaults to the most recent workday.
   */
  public function exportPdf(Request $request): Response|JsonResponse
  {
    $validated = $request->validate([
      'date' => ['sometimes', 'date_format:Y-m-d'],
    ]);

    $date = isset($validated['date'])
      ? Carbon::parse($validated['date'])->startOfDay()
      : $this->latestWorkday();

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

      return (new AttendanceReportPdf())->generate(
        date: $date->toDateString(),
        summary: $daily['summary'],
        late: $daily['late'],
        onLeave: $daily['on_leave'],
        absent: $daily['absent'],
        pending: $daily['pending'],
        cutoffPassed: $daily['cutoff_passed'],
      );
    } catch (\Throwable $e) {
      report($e);

      return response()->json([
        'success' => false,
        'reason'  => 'server_error',
        'message' => 'Failed to generate PDF. Please try again.',
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
