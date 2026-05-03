<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Seminar extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'employee_id',
        'training_program',
        'from_date',
        'to_date',
        'number_of_hours',
        'conducted_by',
        'address',
        'record_status',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
