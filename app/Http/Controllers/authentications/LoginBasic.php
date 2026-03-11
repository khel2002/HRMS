<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginBasic extends Controller
{
  public function index()
  {
    return view('content.authentications.auth-login-basic');
  }

  public function faceRecognitionLogin()
  {
    return view('content.authentications.attendance-employee');
  }
  public function getEnrolledDescriptors()
  {
   
        $employees = Employee::has('faceInfo')
            ->with('faceInfo:employee_id,descriptor')
            ->get();

      
        $formattedData = $employees->map(function ($employee) {
            return [
                'id' => $employee->employee_number,
                'full_name' => "{$employee->first_name} {$employee->last_name}",
                'face_descriptor' => $employee->faceInfo->descriptor,
            ];
        });

        return response()->json($formattedData);
  }
}
