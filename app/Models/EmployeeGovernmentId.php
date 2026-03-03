<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeGovernmentId extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'employee_government_ids';

  protected $fillable = [
    'employee_id',
    'name',
  ];

  protected $casts = [
    'deleted_at' => 'datetime',
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
