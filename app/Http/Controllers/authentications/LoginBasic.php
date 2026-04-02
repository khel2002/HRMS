<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LogImage;
use App\Models\UserLogs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Storage;

class LoginBasic extends Controller
{
  public function index()
  {
    return view('content.authentications.auth-login-basic');
  }
  public function authLogin(Request $request){


    $credentials = $request->validate([
        'username' => 'required',
        'password' => 'required'
    ]);

 
    if (Auth::attempt($credentials)) {

      
        $request->session()->regenerate();

        return redirect()->route('dashboard');
        
    }



    return back()->withErrors([
        'username' => 'Invalid username or password',
    ])->withInput();

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
    try {
        DB::beginTransaction();

        $employeeNumber = $request->employee_id;
        $capturedImage  = $request->captured_image;
        $today          = now()->toDateString();
        $currentTime    = now()->toTimeString();
        $imagePath      = $this->saveFaceImage($capturedImage, $employeeNumber);

        $log = DB::table('user_logs')
            ->where('employee_number', $employeeNumber)
            ->where('log_date', $today)
            ->first();

        $msg = null;
        $targetLogId = null;


        $isAfternoon = now()->isAfter('12:00:00');

        if (!$log) {
            
            $logType = $isAfternoon ? 'afternoon_time_in' : 'morning_time_in';

            $targetLogId = DB::table('user_logs')->insertGetId([
                'employee_number' => $employeeNumber,
                'log_date'        => $today,
                $logType          => $currentTime,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('log_images')->insert([
                'log_id'      => $targetLogId,
                'image_path'  => $imagePath,
                'log_type'    => $logType,
            ]);

            $msg = now()->isAfter('12:00:00')
                ? 'Afternoon Time-In recorded'
                : 'Morning Time-In recorded';
        } else {
            $updateData = [
                'updated_at' => now(),
            ];

            if (is_null($log->morning_time_out) && !$isAfternoon ) {
                $updateData['morning_time_out'] = $currentTime;

                if($this->isvalidNotLog($log->morning_time_in,$currentTime)){
                    return response()->json([
                        'success' => false,
                        'type' => 'warning',
                        'message' => 'Please wait for 10 minutes to logout.',
                    ], 200);
                }

                $imageLog = [
                    'log_id'      => $log->id,
                    'image_path'  => $imagePath,
                    'log_type'    => 'morning_time_out',
                    'captured_at' => now(),
                ];

                $msg = 'Morning Time-Out recorded';
            } elseif (is_null($log->afternoon_time_in) && $isAfternoon) {
                $updateData['afternoon_time_in'] = $currentTime;


               if($this->isvalidNotLog($log->morning_time_out, $currentTime)) {
                    return response()->json([
                        'success' => false,
                        'type' => 'warning',
                        'message' => 'Please wait for 10 minutes to log in.',
                    ], 200);
               }


                $imageLog = [
                    'log_id'      => $log->id,
                    'image_path'  => $imagePath,
                    'log_type'    => 'afternoon_time_in',
                    'captured_at' => now(),
                ];

                $msg = 'Afternoon Time-In recorded';
            } elseif (is_null($log->afternoon_time_out) && $isAfternoon) {
                $updateData['afternoon_time_out'] = $currentTime;


                if($this->isvalidNotLog($log->afternoon_time_in,$currentTime)){
                    return response()->json([
                        'success' => false,
                        'type' => 'warning',
                        'message' => 'Please wait for 10 minutes to log out.',
                    ], 200);
                }

                $imageLog = [
                    'log_id'      => $log->id,
                    'image_path'  => $imagePath,
                    'log_type'    => 'afternoon_time_out',
                    'captured_at' => now(),
                ];

                $msg = 'Afternoon Time-Out recorded';
            } else {
                DB::commit();

                if(is_null($log->afternoon_time_in) && !is_null($log->morning_time_out)){
                    $msg = 'All your logs for the afternoon are already filled.';
                }else{
                    $msg = 'All your logs for today are already filled.';
                }

                return response()->json([
                    'success' => false,
                    'type' => 'warning',
                    'message' => $msg,
                ], 200);
            }

        
            DB::table('user_logs')->where('id', $log->id)->update($updateData);
            DB::table('log_images')->insert($imageLog);

            $targetLogId = $log->id;
        }

        DB::commit();

    
        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to save attendance. Please try again.',
            'error'   => $e->getMessage(),
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

  public function isvalidNotLog($timeIn,$timeNow){
    $timeIn = Carbon::parse($timeIn);
    $timeNow = Carbon::parse($timeNow);

   
    $diffMinutes = $timeIn->diffInMinutes($timeNow);
    

    return $diffMinutes < 10; 
  }
  public function userLogs(){

 


    $userLogs = LogImage::orderBy('captured_at','desc')->get();     
    
    return view('_partials._attendance-log-item', compact('userLogs'))->render();
  }

  public function logout(Request $request)
  {
      Auth::logout();
      $request->session()->invalidate();

      return redirect()->route('home');
  }
}
