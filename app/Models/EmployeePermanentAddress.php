<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeePermanentAddress extends Model
{
  use HasFactory;

  protected $table = 'employee_permanent_address';

  // No timestamps in this table
  public $timestamps = false;

  protected $fillable = [
    'employee_id',
    'house_number',
    'street',
    'subdivision',
    'barangay',
    'city',
    'province',
    'zip_code',
  ];

    // ── Accessors ──────────────────────────────────────────

  /**
   * Get formatted full address string.
   */
  public function getFullAddressAttribute(): string
  {
    return collect([
      $this->house_number,
      $this->street,
      $this->subdivision,
      $this->barangay,
      $this->city,
      $this->province,
      $this->zip_code,
    ])->filter()->implode(', ');
  }

    // ── Relationships ──────────────────────────────────────

  /**
   * Belongs to Employee.
   */
  public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }
}
