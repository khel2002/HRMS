<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AppraisalController extends Controller
{
    /* ─────────────────── LIST ─────────────────── */
 
    public function index(Request $request)
    {
        $query = Appraisal::with([
                'employee.position',   // EmployeePosition
                'employee.office',     // Office
                'evaluator',
            ])
            ->latest('period_end');
 
        // Filter by office (branch)
        if ($request->filled('office_id')) {
            $query->whereHas('employee', fn ($q) =>
                $q->where('office_id', $request->office_id)
            );
        }
 
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
 
        // Search by employee name (first/last) or employee_number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) =>
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
            );
        }
 
        $appraisals = $query->paginate(10)->withQueryString();
 
       
        $offices = \App\Models\Office::orderBy('office_name')->get();
 
        return view('content.admin.evaluations.appraisals.index', compact('appraisals','offices'));
    }
 
    /* ─────────────────── CREATE FORM ─────────────────── */
 
    public function create()
    {
          $employees = Employee::with(['position', 'office'])
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get();


       // dd($employees);
 
        return view('content.admin.evaluations.appraisals.create', compact('employees'));
    }
 
    /* ─────────────────── STORE ─────────────────── */
 
    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());
 
        DB::transaction(function () use ($validated) {
            $appraisal = Appraisal::create($validated);
            $appraisal->computeAndSave();
        });
 
        return redirect()->route('appraisals.index')
            ->with('success', 'Appraisal created successfully.');
    }
 
    /* ─────────────────── SHOW ─────────────────── */
 
    public function show(Appraisal $appraisal)
    {
        $appraisal->load([
            'employee.position',
            'employee.office',
            'evaluator.position',
            'peerEvaluations',
        ]);
 
        return view('content.admin.evaluations.appraisals.show', compact('appraisal'));
    }
 
    /* ─────────────────── EDIT FORM ─────────────────── */
 
    public function edit(Appraisal $appraisal)
    {
        abort_if(
            $appraisal->is_locked,
            403,
            'This appraisal has already been acknowledged and cannot be edited.'
        );
 
        $appraisal->load(['employee.position', 'employee.office']);
 
        $employees = Employee::with(['position', 'office'])
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get();
 
        return view('content.admin.evaluations.appraisals.edit', compact('appraisal', 'employees'));
    }
 
    /* ─────────────────── UPDATE ─────────────────── */
 
    public function update(Request $request, Appraisal $appraisal)
    {
        abort_if($appraisal->is_locked, 403, 'This appraisal has already been acknowledged.');
 
        $validated = $request->validate($this->validationRules());
 
        DB::transaction(function () use ($appraisal, $validated) {
            $appraisal->update($validated);
            $appraisal->computeAndSave();
        });
 
        return redirect()->route('appraisals.show', $appraisal)
            ->with('success', 'Appraisal updated successfully.');
    }
 
    /* ─────────────────── DELETE ─────────────────── */
 
    public function destroy(Appraisal $appraisal)
    {
        abort_if($appraisal->status === 'finalized', 403, 'Finalized appraisals cannot be deleted.');
 
        $appraisal->delete();
 
        return redirect()->route('appraisals.index')
            ->with('success', 'Appraisal deleted.');
    }
 
    /* ─────────────────── SUBMIT (draft → submitted) ─────────────────── */
 
    public function submit(Appraisal $appraisal)
    {
        abort_if($appraisal->status !== 'draft', 422, 'Only draft appraisals can be submitted.');
 
        $appraisal->update(['status' => 'submitted']);
 
        return back()->with('success', 'Appraisal submitted for acknowledgement.');
    }
 
    /* ─────────────────── EMPLOYEE ACKNOWLEDGEMENT ─────────────────── */
 
    public function acknowledge(Request $request, Appraisal $appraisal)
    {
        abort_if($appraisal->status !== 'submitted', 422, 'Appraisal must be in submitted state.');
 
        $request->validate([
            'employee_comments' => 'nullable|string|max:1000',
        ]);
 
        $appraisal->update([
            'status'             => 'acknowledged',
            'employee_comments'  => $request->employee_comments,
            'employee_signed_at' => now(),
        ]);
 
        return back()->with('success', 'Appraisal acknowledged by employee.');
    }
 
    /* ─────────────────── FINALIZE ─────────────────── */
 
    public function finalize(Appraisal $appraisal)
    {
        abort_if($appraisal->status !== 'acknowledged', 422, 'Appraisal must be acknowledged before finalizing.');
 
        $appraisal->update([
            'status'              => 'finalized',
            'evaluator_signed_at' => now(),
        ]);
 
        return back()->with('success', 'Appraisal finalized and filed.');
    }
 
    /* ─────────────────── VALIDATION RULES ─────────────────── */
 
    private function validationRules(): array
    {
        $rating = 'nullable|integer|min:1|max:5';
 
        return [
            'employee_id'    => 'required|exists:employees,id',
            'evaluator_id'   => 'required|exists:employees,id|different:employee_id',
            'period_start'   => 'required|date',
            'period_end'     => 'required|date|after_or_equal:period_start',
 
            // KRA ratings
            'kra1_account_opening'      => $rating,
            'kra2_client_education'     => $rating,
            'kra3_deposit_transactions' => $rating,
            'kra4_compliance_reporting' => $rating,
            'kra5_customer_service'     => $rating,
 
            // KRA remarks
            'kra1_remarks' => 'nullable|string|max:500',
            'kra2_remarks' => 'nullable|string|max:500',
            'kra3_remarks' => 'nullable|string|max:500',
            'kra4_remarks' => 'nullable|string|max:500',
            'kra5_remarks' => 'nullable|string|max:500',
 
            // Behavioral ratings
            'beh1_customer_orientation'     => $rating,
            'beh2_dependability_attendance' => $rating,
            'beh3_teamwork_collaboration'   => $rating,
            'beh4_integrity_conduct'        => $rating,
            'beh5_adaptability_initiative'  => $rating,
 
            // Behavioral remarks
            'beh1_remarks' => 'nullable|string|max:500',
            'beh2_remarks' => 'nullable|string|max:500',
            'beh3_remarks' => 'nullable|string|max:500',
            'beh4_remarks' => 'nullable|string|max:500',
            'beh5_remarks' => 'nullable|string|max:500',
 
            // Recommendations
            'rec_commendation'       => 'boolean',
            'rec_training'           => 'boolean',
            'rec_promotion'          => 'boolean',
            'rec_pip'                => 'boolean',
            'rec_retraining'         => 'boolean',
            'rec_others'             => 'nullable|string|max:500',
 
            // Comments / eligibility
            'evaluator_comments'     => 'nullable|string|max:1000',
            'eligible_for_incentive' => 'boolean',
        ];
    }
}
