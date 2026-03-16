<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePosition extends Model
{
  protected $table = 'employee_position';

  protected $fillable = [
    'position_name',
  ];

  // ── Relationships ─────────────────────────────────────────────────────────

  public function employees()
  {
    return $this->hasMany(Employee::class, 'position_id');
  }
}
