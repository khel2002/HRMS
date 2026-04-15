<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;

class LeaveBalanceSeeder extends Seeder
{
  public function run(): void
  {
    $year = now()->year;

    $leaveTypes = LeaveType::all();

    foreach (Employee::all() as $emp) {
      foreach ($leaveTypes as $type) {
        LeaveBalance::firstOrCreate(
          [
            'employee_id' => $emp->id,
            'leave_type_id' => $type->id,
            'year' => $year,
          ],
          [
            'total_days' => 5,
            'used_days' => 0,
            'remaining_days' => 5,
          ]
        );
      }
    }
  }
}
