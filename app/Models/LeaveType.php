<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

  public function leaveApplications()
  {
    return $this->hasMany(LeaveApplication::class, 'leave_type_id');
  }
}
