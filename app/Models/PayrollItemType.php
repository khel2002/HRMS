<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItemType extends Model
{
  protected $table = 'payroll_item_types';

  protected $fillable = [
    'code',
    'name',
    'category',
    'application_timing',
    'calculation_type',
    'is_taxable',
    'is_mandatory',
    'is_active',
    'sort_order',
    'description',
  ];

  protected $casts = [
    'is_taxable'   => 'boolean',
    'is_mandatory' => 'boolean',
    'is_active'    => 'boolean',
    'sort_order'   => 'integer',
  ];

  const CATEGORIES = [
    'earning',
    'deduction',
  ];

  const APPLICATION_TIMINGS = [
    'every_cutoff',
    'first_cutoff_only',
    'second_cutoff_only',
    'manual_only',
  ];

  const CALCULATION_TYPES = [
    'fixed',
    'manual',
    'formula',
  ];

  public function recurringItems(): HasMany
  {
    return $this->hasMany(EmployeeRecurringPayrollItem::class, 'payroll_item_type_id');
  }

  public function payrollItems(): HasMany
  {
    return $this->hasMany(PayrollItem::class, 'payroll_item_type_id');
  }
}
