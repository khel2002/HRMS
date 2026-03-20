<?php

namespace App\Models;

use App\Models\Employee;
use App\Models\LogImage;
use Illuminate\Database\Eloquent\Model;

class UserLogs extends Model
{
   protected $table = 'user_logs';
    //
   protected $fillable = [
    'employee_number',
    'log_date',
    'morning_time_in',
    'morning_time_out',
    'afternoon_time_in',
    'afternoon_time_out'
   ];


   public function imageLogs(){
      return $this->hasMany(LogImage::class,'log_id');
   }
   public function employee(){
     return $this->belongsTo(Employee::class, 'employee_number', 'employee_number');
   }

}
