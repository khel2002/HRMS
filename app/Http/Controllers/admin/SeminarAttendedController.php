<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Seminar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeminarAttendedController extends Controller
{
    //

    public function seminars()
    {
    
    $seminars = Seminar::with('employee')->get();

    $employees = Employee::where('status', 'active')->get();
    

        return view('content.admin.seminars.seminars_attended', compact('seminars', 'employees'));
    }

   public function store(Request $request)
    {

    //dd($request->all());
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'seminars' => 'required|array|min:1',
            'seminars.*.training_program' => 'required|string',
            'seminars.*.from_date' => 'required|date',
            'seminars.*.to_date' => 'nullable|date',
            'seminars.*.number_of_hours' => 'required|numeric|min:0',
            'seminars.*.conducted_by' => 'nullable|string|max:255',
            'seminars.*.address' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->seminars as $seminar) {
                    Seminar::create([
                        'employee_id' => $request->employee_id,
                        'training_program' => $seminar['training_program'],
                        'from_date' => $seminar['from_date'],
                        'to_date' => $seminar['to_date'] ?? null,
                        'number_of_hours' => $seminar['number_of_hours'],
                        'conducted_by' => $seminar['conducted_by'] ?? null,
                        'address' => $seminar['address'] ?? null,
                        'record_status' => Auth::user()->role_id === 1 ? 'approved' : 'pending',
                    ]);
                }
            });

            return back()->with('success', 'Seminar records added successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to save seminar records. Please try again.');
        }
    }

    public function approve($id){
        $seminar = Seminar::findOrFail($id);

        $seminar->update([
            'record_status' => 'approved'
        ]);

        return back()->with('success', 'Seminar approved successfully.');
    }

    public function reject($id){

        $seminar = Seminar::findOrFail($id);


        $seminar->update([
            'record_status' => 'rejected'
        ]);


        return back()->with('success', 'Seminar was rejected successfully.');
    }

    public function update(Request $request, $id){
        $seminar = Seminar::findOrFail($id);

        $request->validate([
            'training_program' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date',
            'number_of_hours' => 'required|numeric|min:0',
            'conducted_by' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $seminar->update($request->only([
            'training_program',
            'from_date',
            'to_date',
            'number_of_hours',
            'conducted_by',
            'address'
        ]));

        return back()->with('success', 'Seminar updated successfully.');
    }

    public function destroy($id)
    {
     
       $seminar = Seminar::where('id', $id)
            ->where('record_status', 'pending')
            ->firstOrFail();

        $seminar->delete();

        return back()->with('success', 'Seminar deleted successfully.');
    }

    //For Employee

    public function employeeSeminars(){

    $employee = Auth::user()->employee;

        if (!$employee) {
            abort(403, 'Employee profile not found.');
        }

        $seminars = Seminar::with('employee')
            ->where('employee_id', $employee->id)
            ->latest()
            ->get();

    
    


    return view('content.employees.seminars.my-seminars', compact('seminars'));
    }


}