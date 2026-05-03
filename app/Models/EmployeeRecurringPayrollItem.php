<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Flat recurring payroll item — new schema (no payroll_item_type_id FK).
 *
 * Columns:
 *   id, employee_id,
 *   type          ENUM('Earning','Deduction'),
 *   category_code VARCHAR(100)  e.g. 'housing_loan', 'sss_salary_loan',
 *   amount        DECIMAL(10,2),
 *   application_timing ENUM('every_cutoff','first_cutoff_only','second_cutoff_only'),
 *   start_date    DATE nullable,
 *   end_date      DATE nullable,
 *   is_active     BOOLEAN,
 *   notes         TEXT nullable,
 *   created_at, updated_at
 */
class EmployeeRecurringPayrollItem extends Model
{
  protected $table = 'employee_recurring_payroll_items';

  protected $fillable = [
    'employee_id',
    'type',
    'category_code',
    'amount',
    'application_timing',
    'start_date',
    'end_date',
    'is_active',
    'notes',
  ];

  protected $casts = [
    'amount'     => 'decimal:2',
    'start_date' => 'date',
    'end_date'   => 'date',
    'is_active'  => 'boolean',
  ];

  /** Valid category codes — keep in sync with the modal <select> and controller constant. */
  public const CATEGORY_CODES = [
    'sss_salary_loan',
    'sss_calamity_loan',
    'hdmf_loan',
    'hdmf_calamity_loan',
    'sss_mutual_fund',
    'fb_real_estate_loan',
    'fb_vehicle_loan',
    'fb_appliance_loan',
    'fb_medical_loan',
    'fb_medical_loan_2',
    'fb_personal',
    'fb_motorcycle',
    'fb_appliance',
    'fb_vehicle',
    'fb_medical_1',
    'fb_medical_2',
    'fb_educational',
    'housing_loan',
    'accounts_receivable',
    'sss_loan',
    'pagibig_loan',
  ];

  public const APPLICATION_TIMINGS = [
    'every_cutoff',
    'first_cutoff_only',
    'second_cutoff_only',
  ];

  public function employee(): BelongsTo
  {
    return $this->belongsTo(Employee::class, 'employee_id');
  }
}
