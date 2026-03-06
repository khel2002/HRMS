<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;

class EmployeeFaceInfo extends Model
{
    //

    protected $table = 'employee_face_infos';

    protected $fillable = [
        'employee_id',
        'descriptor',
    ];



    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }


}
