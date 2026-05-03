<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
  protected $table = 'payroll_items';

  protected $fillable = [
    'payroll_id',
    'payroll_item_type_id',
    'category',
    'label',
    'amount',
    'quantity',
    'rate',
    'application_timing',
    'is_system_generated',
    'sort_order',
    'notes',
  ];

  protected $casts = [
    'amount'              => 'decimal:2',
    'quantity'            => 'decimal:2',
    'rate'                => 'decimal:2',
    'is_system_generated' => 'boolean',
    'sort_order'          => 'integer',
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

  public function payroll(): BelongsTo
  {
    return $this->belongsTo(Payroll::class, 'payroll_id');
  }

  public function payrollItemType(): BelongsTo
  {
    return $this->belongsTo(PayrollItemType::class, 'payroll_item_type_id');
  }
}
