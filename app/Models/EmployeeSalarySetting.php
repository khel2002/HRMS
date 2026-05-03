<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalarySetting extends Model
{
  protected $table = 'employee_salary_settings';

  protected $fillable = [
    'employee_id',
    'payroll_type',
    'payment_frequency',
    'monthly_rate',
    'semi_monthly_rate',
    'daily_rate',
    'effective_date',
    'is_active',
    'remarks',
  ];

  protected $casts = [
    'monthly_rate'      => 'decimal:2',
    'semi_monthly_rate' => 'decimal:2',
    'daily_rate'        => 'decimal:2',
    'effective_date'    => 'date',
    'is_active'         => 'boolean',
    'payment_frequency' => 'string',
  ];

  public const PAYROLL_TYPES = [
    'semi_monthly',
  ];

  public const PAYMENT_FREQUENCIES = [
    'monthly',
    'first_cutoff',
    'second_cutoff',
    'every_cutoff',
  ];

  public function employee(): BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }
}
