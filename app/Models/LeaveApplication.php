<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplication extends Model
{
  protected $table = 'leave_applications';

  protected $fillable = [
    'employee_id',
    'leave_type_id',
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

  /**
   * Filter by HR verdict (remarks column).
   * The UI uses 'disapproved' but the DB stores 'rejected' — mapped here.
   */
  public function scopeByStatus($query, string $status): mixed
  {
    $dbValue = $status === 'disapproved' ? 'rejected' : $status;

    return $query->where('remarks', $dbValue);
  }

  public function scopeByYear($query, int $year): mixed
  {
    return $query->whereYear('date_filed', $year);
  }

    // ── Accessors ─────────────────────────────────────────────────────────────

  /**
   * Normalise DB `remarks` to UI vocabulary.
   *   DB 'rejected'  → UI 'disapproved'
   *   DB 'pending'   → UI 'pending'
   *   DB 'approved'  → UI 'approved'
   */
  public function getStatusAttribute(): string
  {
    return match ($this->remarks) {
      'rejected' => 'disapproved',
      default    => $this->remarks ?? 'pending',
    };
  }

  /**
   * Build the approval timeline for the view modal.
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
