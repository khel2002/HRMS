<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\Office;
use App\Models\ServiceRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceRecordController extends Controller
{
    //
    public function records()
    {


         $records = ServiceRecord::with([
            'employee',
            'position',
            'office'
        ])->get();


        $employees = Employee::all();
        $offices = Office::all();
        $positions = EmployeePosition::all();

       return view('content.admin.employment-records.employee-service-record', compact('records', 'employees', 'offices', 'positions'));
    }


     public function store(Request $request)
    {

    //dd($request->all());
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.from_date' => ['nullable', 'date'],
            'records.*.to_date' => ['nullable', 'date'],
            'records.*.position_id' => ['nullable', 'exists:employee_position,id'],
            'records.*.office_id' => ['nullable', 'exists:offices,id'],
            'records.*.status_of_appointment' => ['nullable', 'string', 'max:255'],
        ]);


        // dd($request->all());

        DB::beginTransaction();

        try {
            $saved = 0;

            foreach ($request->records as $record) {
                $hasAnyValue =
                    !empty($record['from_date']) ||
                    !empty($record['to_date']) ||
                    !empty($record['position_id']) ||
                    !empty($record['office_id']) ||
                    !empty($record['status_of_appointment']);

                if (!$hasAnyValue) {
                    continue;
                }

                if (
                    empty($record['from_date']) ||
                    empty($record['position_id']) ||
                    empty($record['office_id']) ||
                    empty($record['status_of_appointment'])
                ) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'records' => 'Each filled service entry must have From Date, Position, Department, and Status of Appointment.'
                        ]);
                }

                if (!empty($record['to_date']) && $record['to_date'] < $record['from_date']) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'records' => 'The To Date must be greater than or equal to the From Date.'
                        ]);
                }

                ServiceRecord::create([
                    'employee_id' => $request->employee_id,
                    'from_date' => $record['from_date'],
                    'to_date' => $record['to_date'] ?? null,
                    'position_id' => $record['position_id'],
                    'office_id' => $record['office_id'],
                    'status_of_appointment' => $record['status_of_appointment'],
                    'record_status' => Auth::user()->role_id === 1 ? 'approved' : 'pending',
                ]);

                $saved++;
            }

            if ($saved === 0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'records' => 'Please fill in at least one service entry before saving.'
                    ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Service record added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Something went wrong while saving the service record.'
                ]);
        }
    }

    public function update(Request $request, $id)
    {
        $record = ServiceRecord::where('id', $id)
            ->firstOrFail();

        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'position_id' => 'required|exists:employee_position,id',
            'office_id' => 'required|exists:offices,id',
            'status_of_appointment' => 'required|string',
        ]);

        $record->update($request->only([
            'from_date',
            'to_date',
            'position_id',
            'office_id',
            'status_of_appointment',
        ]));

        return back()->with('success', 'Service record updated successfully.');
    }

    public function approve($id)
    {
        $record = ServiceRecord::findOrFail($id);
        $record->record_status = 'approved';
        $record->save();

        return back()->with('success', 'Record approved.');
    }

    public function reject(Request $request, $id)
    {
        $record = ServiceRecord::findOrFail($id);
        $record->record_status = 'rejected';
        $record->save();

        return back()->with('success', 'Record rejected.');
    }

    public function destroy($id)
    {

       // dd($id);
        $record = ServiceRecord::where('id', $id)
            ->firstOrFail();

        $record->delete();

        return back()->with('success', 'Service record deleted successfully.');
    }



    public function downloadPdf($employeeId)
    {
        $employee = Employee::with(['position', 'office'])->findOrFail($employeeId);

        $records = ServiceRecord::with(['position', 'office'])
            ->where('employee_id', $employee->id)
            ->where('record_status', 'approved')
            ->orderBy('from_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('content.pdf.service-record', compact('employee', 'records'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('service-record-' . $employee->employee_number . '.pdf');
    }


    public function employeeIndex()
    {
        $employee = auth()->user()->employee;

        
       

        $records = ServiceRecord::with(['position', 'office'])
            ->where('employee_id', $employee->id)
            ->get();

        $positions = EmployeePosition::orderBy('position_name')->get();
        $offices = Office::orderBy('office_name')->get();

        return view('content.employees.service-records.service-record', compact('records', 'positions', 'offices'));
    }

    
}