<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\Controller;
use App\Models\UserLogs;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DtrController extends Controller
{
    public function myDtr(Request $request)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            abort(404, 'Employee record not found.');
        }

        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();

        $logs = UserLogs::where('employee_number', $employee->employee_number)
            ->whereBetween('log_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('log_date')
            ->get()
            ->keyBy(function ($log) {
                return Carbon::parse($log->log_date)->format('Y-m-d');
            });

        $days = [];
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $log = $logs->get($dateKey);

            $days[] = [
                'day_number' => $date->day,
                'day_name'   => $date->format('D'),
                'date'       => $dateKey,
                'is_saturday' => $date->isSaturday(),
                'is_sunday'   => $date->isSunday(),
                'log'         => $log,
                'total'       => $log ? $this->calculateRenderedHours($log) : null,
            ];
        }

        return view('content.employees.dtr.my-dtr', compact(
            'employee',
            'month',
            'year',
            'days'
        ));
    }

    private function calculateRenderedHours($log)
    {
        $totalMinutes = 0;

        if ($log->morning_time_in && $log->morning_time_out) {
            $amIn  = Carbon::parse($log->log_date . ' ' . $log->morning_time_in);
            $amOut = Carbon::parse($log->log_date . ' ' . $log->morning_time_out);
            $totalMinutes += $amIn->diffInMinutes($amOut);
        }

        if ($log->afternoon_time_in && $log->afternoon_time_out) {
            $pmIn  = Carbon::parse($log->log_date . ' ' . $log->afternoon_time_in);
            $pmOut = Carbon::parse($log->log_date . ' ' . $log->afternoon_time_out);
            $totalMinutes += $pmIn->diffInMinutes($pmOut);
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

}
