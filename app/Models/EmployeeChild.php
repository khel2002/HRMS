<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeChild extends Model
{
  use HasFactory;

  protected $table = 'employee_children';

  public $timestamps = false;

  protected $fillable = [
    'employee_id',
    'child_name',
    'date_of_birth',
  ];

  protected $casts = [
    'date_of_birth' => 'date',
  ];

    // ── Relationships ──────────────────────────────────────

  /**
   * Belongs to Employee.
   */
  public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }
}
