<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LogImage;
use App\Models\UserLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;

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
 public function storeLog(Request $request)
 {
   try{

    DB::beginTransaction();
   
    $employeeNumber = $request->employee_id; 
    $capturedImage = $request->captured_image; 
    $today = now()->toDateString();
    $currentTime = now()->toTimeString();
    $imagePath = $this->saveFaceImage($capturedImage, $employeeNumber);


    $log = DB::table('user_logs')
        ->where('employee_number', $employeeNumber)
        ->where('log_date', $today)
        ->first();


    $message = null;
    
    if (!$log) {

        $logType = now()->isAfter('12:00:00') ? 'afternoon_time_in' : 'morning_time_in';
                    
        $newLogId = DB::table('user_logs')->insertGetId([
            'employee_number' => $employeeNumber,
            'log_date'        => $today,
            $logType          => $currentTime,
            'created_at'      => now(),
        ]);

        DB::table('log_images')->insert([
            'log_id' => $newLogId,
            'image_path' => $imagePath,
            'log_type' => $logType,
            'captured_at' => now(),
        ]);

        $msg = (now()->isAfter('12:00:00')) ? 'Afternoon Time-In recorded' : 'Morning Time-In recorded';
    }else{

        if (is_null($log->morning_time_out) && now()->isBefore('12:00:00')) {
            $updateData['morning_time_out'] = $currentTime;
            $imageLog = [
                'log_id' => $log->id,
                'image_path' => $imagePath,
                'log_type' => 'morning_time_out',
                'captured_at' => now(),
            ];
            $msg = "Morning Time-Out recorded";
        } elseif (is_null($log->afternoon_time_in)) {
            $updateData['afternoon_time_in'] = $currentTime;
            $imageLog = [
                'log_id' => $log->id,
                'image_path' => $imagePath,
                'log_type' => 'afternoon_time_in',
                'captured_at' => now(),
            ];
            $msg = "Afternoon Time-In recorded";
        } elseif (is_null($log->afternoon_time_out)) {
            $updateData['afternoon_time_out'] = $currentTime;
            $imageLog = [
                'log_id' => $log->id,
                'image_path' => $imagePath,
                'log_type' => 'afternoon_time_out',
                'captured_at' => now(),
            ];
            $msg = "Afternoon Time-Out recorded";
        } else {

           
            return response()->json(['message' => 'All your logs for today are already filled'], 200);
        }


        DB::table('user_logs')->where('id',$log->id)->update($updateData);
        DB::table('log_images')->insert($imageLog);
    }

    DB::commit();

   $userLogs = LogImage::with(['userLogs.employee'])->where('log_id',$newLogId)->first();

    $html = view('_partials._attendance-log-item', compact('userLogs'))->render();

   return response()->json([
            'success' => true,
            'message' => $msg,
            'html' => $html
        ]);

   }catch(\Exception $e){   
    DB::rollback();
 
    return $e;
     return response()->json([
            'status'  => 'error',
            'message' => 'Failed to save attendance. Please try again.' . $e
        ], 500);
   }
  }
  private function saveFaceImage($base64Image, $employeeNumber)
  {
    
  
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
        $image = substr($base64Image, strpos($base64Image, ',') + 1);
        $image = base64_decode($image);

        $imageName = 'attendance_' . $employeeNumber . '_' . time() . '.jpg';
        $path = 'attendance_photos/' . $imageName;

        \Storage::disk('public')->put($path, $image);

        return $path;
    }
    
    return null;
  }
}
