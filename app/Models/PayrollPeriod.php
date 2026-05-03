<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
  protected $table = 'payroll_periods';

  protected $fillable = [
    'name',
    'date_from',
    'date_to',
    'cutoff_type',
    'year',
    'month',
    'status',
    'prepared_by_user_id',
    'approved_by_user_id',
    'released_by_user_id',
    'prepared_at',
    'approved_at',
    'released_at',
    'remarks',
  ];

  protected $casts = [
    'date_from'   => 'date',
    'date_to'     => 'date',
    'prepared_at' => 'datetime',
    'approved_at' => 'datetime',
    'released_at' => 'datetime',
    'year'        => 'integer',
    'month'       => 'integer',
  ];

  const CUTOFF_TYPES = [
    'first_cutoff',
    'second_cutoff',
  ];

  const STATUSES = [
    'draft',
    'generated',
    'computed',
    'approved',
    'released',
    'cancelled',
  ];

  public function payrolls(): HasMany
  {
    return $this->hasMany(Payroll::class, 'payroll_period_id');
  }

  public function preparedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'prepared_by_user_id');
  }

  public function approvedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approved_by_user_id');
  }

  public function releasedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'released_by_user_id');
  }
}
