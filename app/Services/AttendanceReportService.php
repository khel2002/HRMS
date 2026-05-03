<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\UserLogs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceReportService
{
    // ── Schedule Constants ────────────────────────────────────────────────────

  /** Official start of work. Arrivals after this are "Late". */
  const WORK_START      = '08:00:00';

  /** Morning cutoff. Employees with no time-in after this are "Absent". */
  const MORNING_CUTOFF  = '12:00:00';

  /** Afternoon session resumes. */
  const AFTERNOON_START = '13:00:00';

  /** End of working day. */
  const WORK_END        = '17:00:00';

  /** Cache TTL in seconds. */
  const CACHE_TTL = 120;

    // ── Date Validation ───────────────────────────────────────────────────────

  /**
   * Validate whether a date is reportable.
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
   * Response shape:
   * [
   *   'date'        => 'YYYY-MM-DD',
   *   'is_today'    => bool,
   *   'cutoff_passed' => bool,   // true when now >= MORNING_CUTOFF (absent list is active)
   *   'summary'     => [
   *     'total_employees' => int,
   *     'present'         => int,
   *     'late'            => int,
   *     'on_leave'        => int,
   *     'absent'          => int,
   *     'pending'         => int,  // no time-in yet but before cutoff (today only)
   *   ],
   *   'late'        => [...],
   *   'on_leave'    => [...],
   *   'absent'      => [...],
   *   'pending'     => [...],
   * ]
   */
  public function getDailyReport(Carbon $date): array
  {
    $dateStr  = $date->toDateString();
    $isToday  = $dateStr === now()->toDateString();

    // For today, never cache — data changes minute-by-minute.
    if ($isToday) {
      return $this->build($date, $dateStr, $isToday);
    }

    $cacheKey = "attendance.daily.{$dateStr}";
    return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $dateStr) {
      return $this->build($date, $dateStr, false);
    });
  }

  // ── Core Builder ──────────────────────────────────────────────────────────

  private function build(Carbon $date, string $dateStr, bool $isToday): array
  {
    $now           = now();
    $cutoffPassed  = !$isToday || $now->format('H:i:s') >= self::MORNING_CUTOFF;

    // ── 1. Approved leaves that cover this day ─────────────────────────
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

    // ── 2. Time-log records for the day ──────────────────────────────
    $dayLogs = UserLogs::with(['employee.position', 'employee.office'])
      ->where('log_date', $dateStr)
      ->get()
      ->keyBy(fn($l) => $l->employee_number);

    $loggedEmployeeNumbers = $dayLogs->keys()->toArray();

    // ── 3. All active employees ───────────────────────────────────────
    $allEmployees = Employee::with(['position', 'office'])
      ->where('status', 'active')
      ->get();

    $totalEmployees = $allEmployees->count();

    // ── Build: On Leave list ──────────────────────────────────────────
    $onLeave = [];
    $avIdx   = 0;

    foreach ($leavesOnDay as $leave) {
      $emp = $leave->employee;
      if (!$emp) continue;

      $onLeave[] = [
        'name'       => $emp->full_name,
        'pos'        => $emp->position?->position_name ?? '—',
        'dept'       => $emp->office?->office_name     ?? '—',
        'leave_type' => $leave->leaveType?->name       ?? 'Leave',
        'leave_days' => $this->countWorkDays($leave->start_date, $leave->end_date),
        'av'         => $avIdx++ % 6,
      ];
    }

    // ── Build: Late list ──────────────────────────────────────────────
    $late         = [];
    $presentCount = 0;

    foreach ($dayLogs as $log) {
      $emp = $log->employee;
      if (!$emp || $emp->status !== 'active') continue;
      if ($onLeaveEmployeeIds->contains($emp->id)) continue;

      $timeInRaw = $log->morning_time_in ?? $log->afternoon_time_in;

      if (!$timeInRaw) continue;

      $timeIn = Carbon::createFromTimeString($timeInRaw);
      $workStart = Carbon::createFromTimeString(self::WORK_START);

      $isLate = $timeIn->gt($workStart);

      if ($isLate) {
        $minutes = $this->minutesLate($log->morning_time_in);

        $late[] = [
          'name'          => $emp->full_name,
          'pos'           => $emp->position?->position_name ?? '—',
          'dept'          => $emp->office?->office_name     ?? '—',
          'time_in'       => Carbon::parse($log->morning_time_in)->format('g:i A'),
          'late_duration' => $this->formatLate($minutes),
          'late_minutes'  => $minutes,   // used by JS for badge severity colour
          'av'            => $avIdx++ % 6,
        ];
      } else {
        $presentCount++;
      }
    }

    // ── Build: Absent & Pending lists ─────────────────────────────────
    $absent  = [];
    $pending = [];

    foreach ($allEmployees as $emp) {
      // Skip if on approved leave.
      if ($onLeaveEmployeeIds->contains($emp->id)) continue;

      // Skip if they have a time log.
      $hasLog = in_array($emp->employee_number, $loggedEmployeeNumbers);
      if ($hasLog) continue;

      $row = [
        'name' => $emp->full_name,
        'pos'  => $emp->position?->position_name ?? '—',
        'dept' => $emp->office?->office_name     ?? '—',
        'av'   => $avIdx++ % 6,
      ];

      if ($cutoffPassed) {
        // Past 12:00 PM with no time-in → Absent.
        $absent[] = $row;
      } else {
        // Before 12:00 PM with no time-in → Pending (not yet absent).
        $pending[] = $row;
      }
    }

    // ── Sort alphabetically ───────────────────────────────────────────
    $byName = fn($a, $b) => strcmp($a['name'], $b['name']);
    usort($onLeave, $byName);
    usort($late,    $byName);
    usort($absent,  $byName);
    usort($pending, $byName);

    return [
      'date'           => $dateStr,
      'is_today'       => $isToday,
      'cutoff_passed'  => $cutoffPassed,
      'summary'        => [
        'total_employees' => $totalEmployees,
        'present'         => $presentCount,
        'late'            => count($late),
        'on_leave'        => count($onLeave),
        'absent'          => count($absent),
        'pending'         => count($pending),
      ],
      'late'     => $late,
      'on_leave' => $onLeave,
      'absent'   => $absent,
      'pending'  => $pending,
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
  private function minutesLate(?string $timeIn): int
  {
    if (!$timeIn) {
      return 0;
    }

    $workStart = Carbon::createFromTimeString(self::WORK_START);
    $actual    = Carbon::createFromTimeString($timeIn);

    return max(0, $workStart->diffInMinutes($actual, false));
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
