<?php

namespace App\Models;

use App\Models\EmployeeFaceInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    'position_id',
    'office_id',        // ← new: FK → offices.id
  ];

  protected $casts = [
    'date_of_birth' => 'date',
    'height_cm'     => 'decimal:2',
    'weight_kg'     => 'decimal:2',
  ];

  // ── Constants (match the ENUM values in the DB) ───────────────────────────

  const GENDERS        = ['male', 'female', 'other'];
  const CIVIL_STATUSES = ['single', 'married', 'widow'];
  const BLOOD_TYPES    = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
  const STATUSES       = ['active', 'inactive', 'suspended'];

  // ── Accessors ─────────────────────────────────────────────────────────────

  public function getFullNameAttribute(): string
  {
    return trim(
      $this->first_name
        . ($this->middle_name ? ' ' . $this->middle_name : '')
        . ' ' . $this->last_name
    );
  }

  // ── Relationships ─────────────────────────────────────────────────────────

  // One-to-one
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

  public function faceInfo(): HasOne
  {
    return $this->hasOne(EmployeeFaceInfo::class, 'employee_id');
  }

  // One-to-many
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

  public function leaveApplications(): HasMany
  {
    return $this->hasMany(LeaveApplication::class, 'employee_id');
  }

  public function leaveBalances(): HasMany
  {
    return $this->hasMany(LeaveBalance::class, 'employee_id');
  }

  // Belongs-to
  public function position(): BelongsTo
  {
    return $this->belongsTo(EmployeePosition::class, 'position_id');
  }

  public function office(): BelongsTo
  {
    return $this->belongsTo(Office::class, 'office_id');
  }
}
