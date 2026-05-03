<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
  protected $table = 'payrolls';

  protected $fillable = [
    'payroll_period_id',
    'employee_id',
    'employee_salary_setting_id',
    'basic_salary',
    'total_earnings',
    'total_deductions',
    'gross_pay',
    'net_pay',
    'status',
    'remarks',
  ];

  protected $casts = [
    'basic_salary'               => 'decimal:2',
    'total_earnings'             => 'decimal:2',
    'total_deductions'           => 'decimal:2',
    'gross_pay'                  => 'decimal:2',
    'net_pay'                    => 'decimal:2',
  ];

  const STATUSES = [
    'draft',
    'computed',
    'approved',
    'released',
    'void',
  ];

  public function payrollPeriod(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
  }

  public function employee(): BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }

  public function salarySetting(): BelongsTo
  {
    return $this->belongsTo(EmployeeSalarySetting::class, 'employee_salary_setting_id');
  }

  public function items(): HasMany
  {
    return $this->hasMany(PayrollItem::class, 'payroll_id');
  }
}
