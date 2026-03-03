<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EducationLevel extends Model
{
  use HasFactory;

  protected $table = 'education_levels';

  // No timestamps on this lookup table
  public $timestamps = false;

  protected $fillable = [
    'name',
  ];

    // ── Relationships ──────────────────────────────────────

  /**
   * Has many EmployeeEducation records.
   */
  public function educationRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(EmployeeEducation::class, 'level_id');
  }
}
