<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeEducation extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'employee_education';

  protected $fillable = [
    'employee_id',
    'level_id',
    'school_name',
    'degree_course',
    'period_from',
    'period_to',
    'highest_level_units',
    'year_graduated',
    'scholarship_honors',
  ];

  protected $casts = [
    'period_from'    => 'integer',
    'period_to'      => 'integer',
    'year_graduated' => 'integer',
    'deleted_at'     => 'datetime',
  ];

    // ── Relationships ──────────────────────────────────────

  /**
   * Belongs to Employee.
   */
  public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }

  /**
   * Belongs to EducationLevel.
   */
  public function level(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(EducationLevel::class, 'level_id');
  }
}
