<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeFamily extends Model
{
  use HasFactory;

  protected $table = 'employee_family';

  public $timestamps = false;

  protected $fillable = [
    'employee_id',
    'father_name',
    'mother_name',
    'spouse_name',
    'spouse_occupation',
    'spouse_employer',
    'spouse_business_address',
    'emergency_contact_name',
    'emergency_contact_number',
    'emergency_relationship',
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
