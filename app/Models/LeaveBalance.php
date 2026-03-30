<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LeaveBalance extends Model
{
  protected $table = 'leave_balances';

  protected $fillable = [
    'employee_id',
    'leave_type_id',
    'year',
    'total_days',
    'used_days',
    'remaining_days',
  ];

  protected $casts = [
    'year'           => 'integer',
    'total_days'     => 'decimal:2',
    'used_days'      => 'decimal:2',
    'remaining_days' => 'decimal:2',
  ];

    // ── Policy constants ──────────────────────────────────────────────────────

  /**
   * Annual default allocations per leave type (DB name → days).
   * These are the base credits granted at the start of every year.
   */
  const ANNUAL_DEFAULTS = [
    'Vacation Leave'         => 5.0,
    'Sick Leave'             => 5.0,
    'Service Incentive Leave' => 5.0,
  ];

  /**
   * Per-type carry-over caps.
   * null  = no carry-over (balance resets to annual default each year)
   * float = carry-over is allowed, capped at this maximum total_days
   */
  const CARRY_OVER_CAPS = [
    'Vacation Leave'         => null,   // resets annually
    'Sick Leave'             => 14.0,   // unused credits roll over, max 14
    'Service Incentive Leave' => null,  // must be used within the year
  ];

  // ── Relationships ─────────────────────────────────────────────────────────

  public function employee(): BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }

  public function leaveType(): BelongsTo
  {
    return $this->belongsTo(LeaveType::class, 'leave_type_id');
  }

  // ── Scopes ────────────────────────────────────────────────────────────────

  public function scopeForEmployee($query, int $employeeId): mixed
  {
    return $query->where('employee_id', $employeeId);
  }

  public function scopeForType($query, int $leaveTypeId): mixed
  {
    return $query->where('leave_type_id', $leaveTypeId);
  }

  public function scopeForYear($query, int $year): mixed
  {
    return $query->where('year', $year);
  }

    // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * Deduct days from this balance record.
   *
   * Supports decimal values (0.5, 1.5, etc.) for half-day leaves.
   * Uses raw DB arithmetic to avoid floating-point drift.
   * Expects the caller to hold a lockForUpdate() on this row
   * to prevent concurrent double-deductions.
   *
   * @throws \RuntimeException if deduction would exceed remaining balance
   */
  public function deduct(float $days): void
  {
    // Re-fetch fresh values to avoid stale in-memory state.
    // The caller must hold a DB lock before invoking this method.
    $fresh = $this->fresh();

    if ((float) $fresh->remaining_days < $days) {
      throw new \RuntimeException(
        "Insufficient leave balance. Remaining: {$fresh->remaining_days}, Requested: {$days}."
      );
    }

    DB::table($this->table)
      ->where('id', $this->id)
      ->update([
        'used_days'      => DB::raw("used_days + {$days}"),
        'remaining_days' => DB::raw("remaining_days - {$days}"),
      ]);

    $this->refresh();
  }

  /**
   * Resolve (or create) the leave balance row for a given employee, leave type,
   * and year — applying the correct carry-over rule for that leave type.
   *
   * Rules:
   *   - Vacation Leave / SIL : no carry-over, always starts at 5 days.
   *   - Sick Leave            : unused days from the previous year carry over
   *                             and are added to the new year's 5-day allocation,
   *                             capped at a maximum of 14 days total.
   *
   * This is the single authoritative place that creates balance rows, ensuring
   * carry-over is applied consistently whether triggered by approval, year-end
   * rollover command, or first access.
   *
   * @param  int  $employeeId
   * @param  int  $leaveTypeId
   * @param  int  $year
   * @return static
   */
  public static function resolveForYear(int $employeeId, int $leaveTypeId, int $year): static
  {
    // Return immediately if the row already exists.
    $existing = static::where('employee_id', $employeeId)
      ->where('leave_type_id', $leaveTypeId)
      ->where('year', $year)
      ->first();

    if ($existing) {
      return $existing;
    }

    $leaveType = LeaveType::findOrFail($leaveTypeId);
    $typeName  = $leaveType->name;

    $annualDefault = self::ANNUAL_DEFAULTS[$typeName] ?? 5.0;
    $carryOverCap  = self::CARRY_OVER_CAPS[$typeName] ?? null;

    // Carry-over only applies when a cap is defined for this leave type.
    if ($carryOverCap !== null) {
      $totalDays = self::computeCarryOver(
        employeeId: $employeeId,
        leaveTypeId: $leaveTypeId,
        year: $year,
        annualDefault: $annualDefault,
        carryOverCap: $carryOverCap,
      );
    } else {
      // No carry-over: always start fresh at the annual default.
      $totalDays = $annualDefault;
    }

    return static::create([
      'employee_id'    => $employeeId,
      'leave_type_id'  => $leaveTypeId,
      'year'           => $year,
      'total_days'     => $totalDays,
      'used_days'      => 0.0,
      'remaining_days' => $totalDays,
    ]);
  }

  /**
   * Compute the total_days for a new year's balance row by looking at how many
   * days the employee had remaining at the end of the previous year, then adding
   * the annual default and capping at the policy maximum.
   *
   * Example (Sick Leave, cap = 14):
   *   Previous year remaining = 3  →  carried = 3
   *   New year total          = 3 + 5 = 8   (under cap, no adjustment)
   *
   *   Previous year remaining = 11 →  carried = 11
   *   New year total          = 11 + 5 = 16  → capped at 14
   *
   *   No previous year row    →  new year total = 5  (fresh start)
   */
  private static function computeCarryOver(
    int   $employeeId,
    int   $leaveTypeId,
    int   $year,
    float $annualDefault,
    float $carryOverCap,
  ): float {
    $previousRow = static::where('employee_id', $employeeId)
      ->where('leave_type_id', $leaveTypeId)
      ->where('year', $year - 1)
      ->first();

    $carriedOver = $previousRow ? (float) $previousRow->remaining_days : 0.0;
    $computed    = $carriedOver + $annualDefault;

    return min($computed, $carryOverCap);
  }

  /**
   * Roll over ALL employees' sick leave balances from $fromYear into $toYear.
   *
   * Intended to be called by a scheduled Artisan command at the start of
   * each new year (e.g. schedule()->command('leave:rollover')->yearlyOn(1, 1, '00:00')).
   *
   * Only processes leave types that have a carry-over cap defined.
   * Skips any employee+type+year combination that already has a row
   * (idempotent — safe to re-run).
   *
   * @param  int  $fromYear  The year whose remaining balances are carried over.
   * @param  int  $toYear    The year to create new balance rows for.
   * @return array{processed: int, skipped: int}
   */
  public static function rolloverYear(int $fromYear, int $toYear): array
  {
    $processed = 0;
    $skipped   = 0;

    // Only roll over leave types that have a carry-over policy.
    $carryOverTypeNames = array_keys(array_filter(self::CARRY_OVER_CAPS));
    $leaveTypes = LeaveType::whereIn('name', $carryOverTypeNames)->get();

    foreach ($leaveTypes as $leaveType) {
      $annualDefault = self::ANNUAL_DEFAULTS[$leaveType->name] ?? 5.0;
      $carryOverCap  = self::CARRY_OVER_CAPS[$leaveType->name];

      // Fetch all employees who have a balance row for $fromYear.
      $previousRows = static::where('leave_type_id', $leaveType->id)
        ->where('year', $fromYear)
        ->get();

      foreach ($previousRows as $prev) {
        // Skip if a row for $toYear already exists (idempotent).
        $alreadyExists = static::where('employee_id', $prev->employee_id)
          ->where('leave_type_id', $leaveType->id)
          ->where('year', $toYear)
          ->exists();

        if ($alreadyExists) {
          $skipped++;
          continue;
        }

        $carriedOver = (float) $prev->remaining_days;
        $totalDays   = min($carriedOver + $annualDefault, $carryOverCap);

        static::create([
          'employee_id'    => $prev->employee_id,
          'leave_type_id'  => $leaveType->id,
          'year'           => $toYear,
          'total_days'     => $totalDays,
          'used_days'      => 0.0,
          'remaining_days' => $totalDays,
        ]);

        $processed++;
      }
    }

    return ['processed' => $processed, 'skipped' => $skipped];
  }
}
