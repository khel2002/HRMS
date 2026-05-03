<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRecord extends Model
{

     use SoftDeletes;
     
    protected $table = 'service_records';

    protected $fillable = [
        'employee_id',
        'from_date',
        'to_date',
        'position_id',
        'office_id',
        'status_of_appointment',
        'record_status',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class, 'position_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }
}
