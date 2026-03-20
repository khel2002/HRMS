<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
  protected $table = 'leave_types';

  protected $fillable = [
    'name',
    'max_days',
  ];

  protected $casts = [
    'max_days' => 'integer',
  ];

  // ── Relationships ─────────────────────────────────────────────────────────

  public function leaveApplications(): HasMany
  {
    return $this->hasMany(LeaveApplication::class, 'leave_type_id');
  }

  public function leaveBalances(): HasMany
  {
    return $this->hasMany(LeaveBalance::class, 'leave_type_id');
  }
}
