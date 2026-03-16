<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
  protected $table = 'leave_applications';

  protected $fillable = [
    'employee_id',
    'leave_type_id',
    'department',
    'cause',
    'date_filed',
    'start_date',
    'end_date',
    'total_days',
    'manager_status',   // pending | recommended | not_recommended
    'reason',
    'remarks',          // pending | approved | rejected
  ];

  protected $casts = [
    'date_filed' => 'datetime',
    'start_date' => 'date',
    'end_date'   => 'date',
    'total_days' => 'integer',
  ];

  // ── Constants ─────────────────────────────────────────────────────────────

  const MANAGER_STATUSES = ['pending', 'recommended', 'not_recommended'];
  const REMARKS          = ['pending', 'approved', 'rejected'];

  // ── Relationships ─────────────────────────────────────────────────────────

  public function employee()
  {
    return $this->belongsTo(Employee::class);
  }

  public function leaveType()
  {
    return $this->belongsTo(LeaveType::class, 'leave_type_id');
  }

  // ── Scopes ────────────────────────────────────────────────────────────────

  public function scopeForEmployee($query, int $employeeId)
  {
    return $query->where('employee_id', $employeeId);
  }

  /**
   * Filter by the HR remarks status (pending | approved | rejected).
   * Maps to the `remarks` column since that's the overall HR decision field.
   * 'disapproved' from the UI maps to 'rejected' in the DB.
   */
  public function scopeByStatus($query, string $status)
  {
    $dbValue = $status === 'disapproved' ? 'rejected' : $status;
    return $query->where('remarks', $dbValue);
  }

  public function scopeByYear($query, int $year)
  {
    return $query->whereYear('date_filed', $year);
  }

    // ── Accessors ─────────────────────────────────────────────────────────────

  /**
   * Normalise DB `remarks` to UI status vocabulary.
   *   'rejected'  → 'disapproved'
   *   'pending'   → 'pending'
   *   'approved'  → 'approved'
   */
  public function getStatusAttribute(): string
  {
    return match ($this->remarks) {
      'rejected' => 'disapproved',
      default    => $this->remarks ?? 'pending',
    };
  }

  /**
   * Build a timeline array for the view modal.
   */
  public function getTimelineAttribute(): array
  {
    $steps = [];

    $steps[] = [
      'label' => 'Application Filed',
      'date'  => $this->date_filed?->toDateString(),
      'note'  => 'Leave application submitted.',
    ];

    if ($this->manager_status === 'recommended') {
      $steps[] = [
        'label' => 'Recommended by Manager',
        'date'  => $this->updated_at?->toDateString(),
        'note'  => null,
      ];
    }

    if ($this->manager_status === 'not_recommended') {
      $steps[] = [
        'label' => 'Not Recommended by Manager',
        'date'  => $this->updated_at?->toDateString(),
        'note'  => $this->reason,
      ];
    }

    if ($this->remarks === 'approved') {
      $steps[] = [
        'label' => 'Approved by HR',
        'date'  => $this->updated_at?->toDateString(),
        'note'  => null,
      ];
    }

    if ($this->remarks === 'rejected') {
      $steps[] = [
        'label' => 'Rejected by HR',
        'date'  => $this->updated_at?->toDateString(),
        'note'  => $this->reason,
      ];
    }

    return $steps;
  }
}
