<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'employees';

  protected $fillable = [
    'employee_number',
    'first_name',
    'middle_name',
    'last_name',
    'citizenship',
    'gender',
    'date_of_birth',
    'place_of_birth',
    'mobile_number',
    'landline_number',
    'civil_status',
    'height_cm',
    'weight_kg',
    'blood_type',
  ];

  protected $casts = [
    'date_of_birth' => 'date',
    'height_cm'     => 'decimal:2',
    'weight_kg'     => 'decimal:2',
    'deleted_at'    => 'datetime',
  ];

  // ── Enums ──────────────────────────────────────────────
  const GENDERS = ['male', 'female', 'other'];

  const CIVIL_STATUSES = ['single', 'married', 'widow'];

  const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    // ── Accessors ──────────────────────────────────────────

  /**
   * Get full name (First Middle Last).
   */
  public function getFullNameAttribute(): string
  {
    return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
  }

  /**
   * Get full name (Last, First Middle).
   */
  public function getFormalNameAttribute(): string
  {
    $middle = $this->middle_name ? " {$this->middle_name}" : '';
    return "{$this->last_name}, {$this->first_name}{$middle}";
  }

    // ── Relationships ──────────────────────────────────────

  /**
   * One-to-one: Employee has one User account.
   */
  public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
  {
    return $this->hasOne(User::class, 'employee_id');
  }

  /**
   * One-to-one: Employee has one permanent address.
   */
  public function permanentAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
  {
    return $this->hasOne(EmployeePermanentAddress::class, 'employee_id');
  }

  /**
   * One-to-one: Employee has one current address.
   */
  public function currentAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
  {
    return $this->hasOne(EmployeeCurrentAddress::class, 'employee_id');
  }

  /**
   * One-to-one: Employee has one family record.
   */
  public function family(): \Illuminate\Database\Eloquent\Relations\HasOne
  {
    return $this->hasOne(EmployeeFamily::class, 'employee_id');
  }

  /**
   * One-to-many: Employee has many children.
   */
  public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(EmployeeChild::class, 'employee_id');
  }

  /**
   * One-to-many: Employee has many education records.
   */
  public function education(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(EmployeeEducation::class, 'employee_id');
  }

  /**
   * One-to-many: Employee has many government IDs.
   */
  public function governmentIds(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(EmployeeGovernmentId::class, 'employee_id');
  }
}
