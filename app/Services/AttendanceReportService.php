<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\UserLogs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceReportService
{
    // ── Constants ─────────────────────────────────────────────────────────────

  /** Work-day start time (HH:MM:SS). Arrivals after this are marked Late. */
  const WORK_START = '08:30:00';

  /** Cache TTL in seconds. */
  const CACHE_TTL = 120;

    // ── Date Validation ───────────────────────────────────────────────────────

  /**
   * Validate whether a date is reportable:
   *   ✗  Tomorrow or later → reason: 'future'
   *   ✗  Saturday/Sunday   → reason: 'weekend'
   *   ✓  Any past/today weekday
   *
   * @return array{ allowed: bool, reason: string|null, message: string|null }
   */
  public function validateReportDate(Carbon $date): array
  {
    if ($date->toDateString() > now()->toDateString()) {
      return [
        'allowed' => false,
        'reason'  => 'future',
        'message' => 'No available data yet for this date.',
      ];
    }

    if ($date->isWeekend()) {
      return [
        'allowed' => false,
        'reason'  => 'weekend',
        'message' => 'Attendance is not recorded on weekends (Saturday & Sunday).',
      ];
    }

    return ['allowed' => true, 'reason' => null, 'message' => null];
  }

    // ── Daily Report ──────────────────────────────────────────────────────────

  /**
   * Return the daily attendance report for a validated workday.
   *
   * Always call validateReportDate() before this method.
   *
   * Response shape:
   * [
   *   'date'     => 'YYYY-MM-DD',
   *   'summary'  => [
   *     'total_employees' => int,   // all active employees
   *     'present'         => int,   // logged in and not on leave, not late
   *     'late'            => int,   // logged in but after WORK_START
   *     'on_leave'        => int,   // approved leave covering this day
   *   ],
   *   'late'     => [ ['name','pos','dept','time_in','late_duration','av'], ... ],
   *   'on_leave' => [ ['name','pos','dept','leave_type','leave_days','av'], ... ],
   * ]
   */
  public function getDailyReport(Carbon $date): array
  {
    $dateStr  = $date->toDateString();
    $cacheKey = "attendance.daily.{$dateStr}";

    return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $dateStr) {
      return $this->build($date, $dateStr);
    });
  }

  // ── Core Builder ──────────────────────────────────────────────────────────

  private function build(Carbon $date, string $dateStr): array
  {
    // ── 1. Approved leaves that cover this day ────────────────────────────
    $leavesOnDay = LeaveApplication::with([
      'employee.office',
      'employee.position',
      'leaveType',
    ])
      ->where('remarks', 'approved')
      ->where('start_date', '<=', $dateStr)
      ->where('end_date',   '>=', $dateStr)
      ->get();

    $onLeaveEmployeeIds = $leavesOnDay->pluck('employee_id')->unique();

    // ── 2. Time-log records for the day ───────────────────────────────────
    $dayLogs = UserLogs::with(['employee.position', 'employee.office'])
      ->where('log_date', $dateStr)
      ->get()
      ->keyBy(fn($l) => $l->employee_number);

    // ── 3. Total active headcount (for summary) ───────────────────────────
    $totalEmployees = Employee::where('status', 'active')->count();

    // ── Build: On Leave list ──────────────────────────────────────────────
    $onLeave = [];
    $avIdx   = 0;

    foreach ($leavesOnDay as $leave) {
      $emp = $leave->employee;
      if (!$emp) continue;

      $onLeave[] = [
        'name'        => $emp->full_name,
        'pos'         => $emp->position?->position_name ?? '—',
        'dept'        => $emp->office?->office_name     ?? '—',
        'leave_type'  => $leave->leaveType?->name       ?? 'Leave',
        'leave_days'  => $this->countWorkDays($leave->start_date, $leave->end_date),
        'av'          => $avIdx++ % 6,
      ];
    }

    // ── Build: Late list ──────────────────────────────────────────────────
    $late         = [];
    $presentCount = 0;

    foreach ($dayLogs as $log) {
      $emp = $log->employee;
      if (!$emp || $emp->status !== 'active') continue;
      if ($onLeaveEmployeeIds->contains($emp->id)) continue;

      $isLate = $log->morning_time_in && $log->morning_time_in > self::WORK_START;

      if ($isLate) {
        $minutes = $this->minutesLate($log->morning_time_in);

        $late[] = [
          'name'          => $emp->full_name,
          'pos'           => $emp->position?->position_name ?? '—',
          'dept'          => $emp->office?->office_name     ?? '—',
          'time_in'       => Carbon::parse($log->morning_time_in)->format('g:i A'),
          'late_duration' => $this->formatLate($minutes),
          'av'            => $avIdx++ % 6,
        ];
      } else {
        // On time.
        $presentCount++;
      }
    }

    // ── Sort alphabetically ───────────────────────────────────────────────
    $byName = fn($a, $b) => strcmp($a['name'], $b['name']);
    usort($onLeave, $byName);
    usort($late,    $byName);

    // ── Summary ───────────────────────────────────────────────────────────
    $onLeaveCount = count($onLeave);
    $lateCount    = count($late);

    return [
      'date'     => $dateStr,
      'summary'  => [
        'total_employees' => $totalEmployees,
        'present'         => $presentCount,
        'late'            => $lateCount,
        'on_leave'        => $onLeaveCount,
      ],
      'late'     => $late,
      'on_leave' => $onLeave,
    ];
  }

    // ── Private Helpers ───────────────────────────────────────────────────────

  /**
   * Count Mon–Fri workdays in a date range (inclusive). Returns at least 1.
   */
  private function countWorkDays(Carbon $start, Carbon $end): int
  {
    $days   = 0;
    $cursor = $start->copy()->startOfDay();

    while ($cursor->lte($end->startOfDay())) {
      if (!$cursor->isWeekend()) {
        $days++;
      }
      $cursor->addDay();
    }

    return max(1, $days);
  }

  private function minutesLate(string $timeIn): int
  {
    $workStart = Carbon::today()->setTimeFromTimeString(self::WORK_START);
    $actual    = Carbon::today()->setTimeFromTimeString($timeIn);
    return max(0, (int) $workStart->diffInMinutes($actual, false));
  }

  private function formatLate(int $minutes): string
  {
    if ($minutes < 60) {
      return "{$minutes} min";
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
  }
}
