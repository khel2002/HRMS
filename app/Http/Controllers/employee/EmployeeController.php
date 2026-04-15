<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
  public function dashboard()
  {
    return view('content.employee.dashboard.dashboard-hris');
  }
}
