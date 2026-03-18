<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'total_days'     => 'integer',
    'used_days'      => 'integer',
    'remaining_days' => 'integer',
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

  /**
   * Filter to a specific employee + leave type + year.
   * Used by LeaveApplicationController when checking / deducting balances.
   */
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
   * Keeps used_days and remaining_days in sync atomically.
   *
   * @throws \RuntimeException if deduction would exceed total allocation
   */
  public function deduct(int|float $days): void
  {
    $days = (int) ceil($days); // round half-days up to nearest integer for balance tracking

    if ($this->remaining_days < $days) {
      throw new \RuntimeException(
        "Insufficient leave balance. Remaining: {$this->remaining_days}, Requested: {$days}."
      );
    }

    $this->increment('used_days', $days);
    $this->decrement('remaining_days', $days);
  }
}
