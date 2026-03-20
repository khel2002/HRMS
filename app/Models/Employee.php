<?php

namespace App\Models;

use App\Models\EmployeeFaceInfo;
use App\Models\UserLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
  use SoftDeletes;

  protected $table = 'employees';

  protected $fillable = [
    'employee_number',
    'first_name',
    'middle_name',
    'last_name',
    'email',
    'citizenship',
    'gender',
    'date_of_birth',
    'place_of_birth',
    'mobile_number',
    'civil_status',
    'height_cm',
    'weight_kg',
    'blood_type',
    'status',
  ];

  protected $casts = [
    'date_of_birth' => 'date',
    'height_cm'     => 'decimal:2',
    'weight_kg'     => 'decimal:2',
  ];

    // ── Constants — must match DB enum values exactly ──────────────

  /** employees.gender enum */
  const GENDERS = ['male', 'female', 'other'];

  /** employees.civil_status enum — DB: single, married, widow */
  const CIVIL_STATUSES = ['single', 'married', 'widow'];

  const STATUSES = ['active', 'inactive', 'suspended'];

  /** employees.blood_type enum */
  const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

  // ── Relationships ──────────────────────────────────────────────

  public function permanentAddress(): HasOne
  {
    return $this->hasOne(EmployeePermanentAddress::class, 'employee_id');
  }

  public function currentAddress(): HasOne
  {
    return $this->hasOne(EmployeeCurrentAddress::class, 'employee_id');
  }

  public function family(): HasOne
  {
    return $this->hasOne(EmployeeFamily::class, 'employee_id');
  }

  public function children(): HasMany
  {
    return $this->hasMany(EmployeeChild::class, 'employee_id');
  }

  public function education(): HasMany
  {
    return $this->hasMany(EmployeeEducation::class, 'employee_id');
  }

  public function governmentIds(): HasMany
  {
    return $this->hasMany(EmployeeGovernmentId::class, 'employee_id');
  }
  public function faceInfo(): HasOne
  {
      return $this->hasOne(EmployeeFaceInfo::class, 'employee_id');
  }
  public function userLogs(){
    return $this->hasMany(UserLogs::class,'employee_number');
  }
}
