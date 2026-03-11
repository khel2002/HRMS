<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeChild;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFaceInfo;
use App\Models\EmployeeGovernmentId;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class EmployeesRegistrationController extends Controller
{

  private function employeeRules(int $ignoreId = 0): array
  {
    return [
      'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($ignoreId)],
      'first_name'      => ['required', 'string', 'max:100'],
      'middle_name'     => ['nullable', 'string', 'max:100'],
      'last_name'       => ['required', 'string', 'max:100'],
      'email'           => ['required', 'string', 'email', 'max:150'],
      'citizenship'     => ['required', 'string', 'max:100'],
      'gender'          => ['required', Rule::in(Employee::GENDERS)],
      'date_of_birth'   => ['required', 'date'],
      'place_of_birth'  => ['required', 'string', 'max:150'],
      'mobile_number'   => ['required', 'string', 'max:20'],
      'civil_status'    => ['required', Rule::in(['single', 'married', 'widow'])],
      'height_cm'       => ['required', 'numeric', 'min:0', 'max:300'],
      'weight_kg'       => ['required', 'numeric', 'min:0', 'max:500'],
      'blood_type'      => ['required', Rule::in(Employee::BLOOD_TYPES)],
    ];
  }

  private function relatedRules(): array
  {
    return [
      // Permanent address — free text
      'permanent.house_number'   => ['nullable', 'string', 'max:50'],
      'permanent.street'         => ['nullable', 'string', 'max:100'],
      'permanent.subdivision'    => ['nullable', 'string', 'max:100'],
      'permanent.zip_code'       => ['nullable', 'string', 'max:20'],
      // Permanent address — PSGC hierarchy (name stored, code for chaining only)
      'permanent.region'         => ['nullable', 'string', 'max:150'],
      'permanent.region_code'    => ['nullable', 'string', 'max:20'],
      'permanent.province'       => ['nullable', 'string', 'max:150'],
      'permanent.province_code'  => ['nullable', 'string', 'max:20'],
      'permanent.city'           => ['nullable', 'string', 'max:150'],
      'permanent.city_code'      => ['nullable', 'string', 'max:20'],
      'permanent.barangay'       => ['nullable', 'string', 'max:150'],

      // Current address — free text
      'current.house_number'     => ['nullable', 'string', 'max:50'],
      'current.street'           => ['nullable', 'string', 'max:100'],
      'current.subdivision'      => ['nullable', 'string', 'max:100'],
      'current.zip_code'         => ['nullable', 'string', 'max:20'],
      // Current address — PSGC hierarchy
      'current.region'           => ['nullable', 'string', 'max:150'],
      'current.region_code'      => ['nullable', 'string', 'max:20'],
      'current.province'         => ['nullable', 'string', 'max:150'],
      'current.province_code'    => ['nullable', 'string', 'max:20'],
      'current.city'             => ['nullable', 'string', 'max:150'],
      'current.city_code'        => ['nullable', 'string', 'max:20'],
      'current.barangay'         => ['nullable', 'string', 'max:150'],

      'family.father_name'              => ['required', 'string', 'max:150'],
      'family.mother_name'              => ['required', 'string', 'max:150'],
      'family.spouse_name'              => ['nullable', 'string', 'max:150'],
      'family.spouse_occupation'        => ['nullable', 'string', 'max:100'],
      'family.spouse_employer'          => ['nullable', 'string', 'max:150'],
      'family.spouse_business_address'  => ['nullable', 'string', 'max:255'],
      'family.emergency_contact_name'   => ['required', 'string', 'max:150'],
      'family.emergency_contact_number' => ['required', 'string', 'max:20'],
      'family.emergency_relationship'   => ['required', 'string', 'max:50'],

      'children'                 => ['nullable', 'array'],
      'children.*.child_name'    => ['nullable', 'string', 'max:150'],
      'children.*.date_of_birth' => ['nullable', 'date'],

      'education'                       => ['nullable', 'array'],
      'education.*.level_id'            => ['required', 'integer', 'between:1,5'],
      'education.*.school_name'         => ['nullable', 'string', 'max:255'],
      'education.*.degree_course'       => ['nullable', 'string', 'max:150'],
      'education.*.period_from'         => ['nullable', 'integer', 'min:1950', 'max:2099'],
      'education.*.period_to'           => ['nullable', 'integer', 'min:1950', 'max:2099'],
      'education.*.highest_level_units' => ['nullable', 'string', 'max:50'],
      'education.*.year_graduated'      => ['nullable', 'integer', 'min:1950', 'max:2099'],
      'education.*.scholarship_honors'  => ['nullable', 'string', 'max:255'],

      'gov_ids'        => ['nullable', 'array'],
      'gov_ids.*.name' => ['nullable', 'string', 'max:255'],
    ];
  }

  private function saveRelated(Employee $employee, Request $request): void
  {
    // Map PSGC form fields → DB columns (region/codes have no DB column)
    $permRaw = $request->input('permanent', []);
    $employee->permanentAddress()->updateOrCreate(
      ['employee_id' => $employee->id],
      [
        'house_number' => $permRaw['house_number'] ?? '',
        'street'       => $permRaw['street']       ?? '',
        'subdivision'  => $permRaw['subdivision']  ?? '',
        'barangay'     => $permRaw['barangay']      ?? '',
        'city'         => $permRaw['city']          ?? '',
        'province'     => $permRaw['province']      ?? '',
        'zip_code'     => $permRaw['zip_code']      ?? '',
      ]
    );

    $currRaw = $request->input('current', []);
    $employee->currentAddress()->updateOrCreate(
      ['employee_id' => $employee->id],
      [
        'house_number' => $currRaw['house_number'] ?? '',
        'street'       => $currRaw['street']       ?? '',
        'subdivision'  => $currRaw['subdivision']  ?? '',
        'barangay'     => $currRaw['barangay']      ?? '',
        'city'         => $currRaw['city']          ?? '',
        'province'     => $currRaw['province']      ?? '',
        'zip_code'     => $currRaw['zip_code']      ?? '',
      ]
    );

    if ($request->filled('family')) {
      $employee->family()->updateOrCreate(
        ['employee_id' => $employee->id],
        $request->input('family')
      );
    }

    $this->syncHasMany(
      relation: $employee->children(),
      model: EmployeeChild::class,
      rows: collect($request->input('children', []))
        ->filter(fn($c) => !empty($c['child_name']))
        ->map(fn($c) => [
          'employee_id'   => $employee->id,
          'child_name'    => $c['child_name'],
          'date_of_birth' => $c['date_of_birth'] ?? null,
        ])
        ->values()
        ->all()
    );

    $this->syncHasMany(
      relation: $employee->education(),
      model: EmployeeEducation::class,
      rows: collect($request->input('education', []))
        ->filter(fn($e) => !empty($e['school_name']))
        ->map(fn($e) => [
          'employee_id'         => $employee->id,
          'level_id'            => $e['level_id'],
          'school_name'         => $e['school_name'],
          'degree_course'       => $e['degree_course']       ?? null,
          'period_from'         => $e['period_from']         ?? null,
          'period_to'           => $e['period_to']           ?? null,
          'highest_level_units' => $e['highest_level_units'] ?? null,
          'year_graduated'      => $e['year_graduated']      ?? null,
          'scholarship_honors'  => $e['scholarship_honors']  ?? null,
        ])
        ->values()
        ->all()
    );

    $this->syncHasMany(
      relation: $employee->governmentIds(),
      model: EmployeeGovernmentId::class,
      rows: collect($request->input('gov_ids', []))
        ->filter(fn($g) => !empty($g['name']))
        ->map(fn($g) => [
          'employee_id' => $employee->id,
          'name'        => $g['name'],
        ])
        ->values()
        ->all()
    );
  }

  /**
   * Delete all existing rows for a has-many relation then bulk-insert new ones.
   * Extracted to avoid repeating the delete + conditional insert pattern.
   */
  private function syncHasMany(object $relation, string $model, array $rows): void
  {
    $relation->delete();

    if (!empty($rows)) {
      $model::insert($rows);
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  INDEX
  // ──────────────────────────────────────────────────────────────

  public function index()
  {
    return view('content.admin.employees-registration.employees-registration');
  }

  // ──────────────────────────────────────────────────────────────
  //  CREATE
  // ──────────────────────────────────────────────────────────────

  public function create()
  {
    return view('content.admin.employees-registration.employees-registration');
  }

  // ──────────────────────────────────────────────────────────────
  //  STORE
  // ──────────────────────────────────────────────────────────────

  public function store(Request $request): RedirectResponse
  {
    // ── STEP 1: Log everything that arrived in the POST ───────────
    Log::info('STORE called', [
      'method'       => $request->method(),
      'all_keys'     => array_keys($request->all()),
      'employee_num' => $request->input('employee_number'),
      'civil_status' => $request->input('civil_status'),
      'gender'       => $request->input('gender'),
      'email'        => $request->input('email'),
      'perm_keys'    => array_keys($request->input('permanent', [])),
      'curr_keys'    => array_keys($request->input('current', [])),
      'family_keys'  => array_keys($request->input('family', [])),
    ]);

    // ── STEP 2: Try validation, log any failures ──────────────────
    $rules = array_merge($this->employeeRules(), $this->relatedRules());

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      Log::warning('STORE validation failed', ['errors' => $validator->errors()->toArray()]);
      return redirect()
        ->back()
        ->withErrors($validator)
        ->withInput()
        ->with('validation_failed', true);
    }

    // ── STEP 3: Try DB insert ─────────────────────────────────────
    try {
      DB::transaction(function () use ($request) {
        $employee = Employee::create(array_merge(
          $request->only([
            'employee_number',
            'first_name',
            'middle_name',
            'last_name',
            'citizenship',
            'gender',
            'date_of_birth',
            'place_of_birth',
            'mobile_number',
            'email',
            'civil_status',
            'height_cm',
            'weight_kg',
            'blood_type',
          ]),
          ['status' => 'inactive']   // always inactive until face enrollment
        ));

        Log::info('STORE employee created', ['id' => $employee->id]);

        $this->saveRelated($employee, $request);

        Log::info('STORE related saved', ['id' => $employee->id]);
      });

      Log::info('STORE SUCCESS');

      return redirect()
        ->route('employees-index')
        ->with('success', 'Employee successfully registered.');
    } catch (Throwable $e) {
      Log::error('STORE DB failed', [
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
      ]);

      return redirect()
        ->back()
        ->withInput()
        ->with('error', 'DB error: ' . $e->getMessage());
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  SHOW
  // ──────────────────────────────────────────────────────────────

  public function show(int $id): mixed
  {
    try {
      $employee = $this->findWithRelations($id);

      return view('content.admin.employees-registration.view-employee', compact('employee'));
    } catch (ModelNotFoundException) {
      return redirect()->route('employees-index')->with('error', 'Employee record not found.');
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  EDIT
  // ──────────────────────────────────────────────────────────────

  public function edit(int $id): mixed
  {
    try {
      $employee = $this->findWithRelations($id);

      return view('content.admin.employees-registration.edit-employee', compact('employee'));
    } catch (ModelNotFoundException) {
      return redirect()->route('employees-index')->with('error', 'Employee record not found.');
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  UPDATE
  // ──────────────────────────────────────────────────────────────

  public function update(Request $request, int $id): RedirectResponse
  {
    try {
      $employee = Employee::findOrFail($id);
    } catch (ModelNotFoundException) {
      return redirect()->route('employees-index')->with('error', 'Employee record not found.');
    }

    $request->validate(
      array_merge($this->employeeRules($employee->id), $this->relatedRules())
    );

    try {
      DB::transaction(function () use ($request, $employee) {
        $employee->update($request->only([
          'employee_number',
          'first_name',
          'middle_name',
          'last_name',
          'citizenship',
          'gender',
          'date_of_birth',
          'place_of_birth',
          'mobile_number',
          'email',
          'civil_status',
          'height_cm',
          'weight_kg',
          'blood_type',
        ]));

        $this->saveRelated($employee, $request);
      });

      return redirect()
        ->route('employee-show', $id)
        ->with('success', 'Employee record updated successfully.');
    } catch (Throwable $e) {
      Log::error('Employee update failed', ['id' => $id, 'error' => $e->getMessage()]);

      return redirect()
        ->back()
        ->withInput()
        ->with('error', 'Something went wrong while updating the employee. Please try again.');
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  DESTROY
  // ──────────────────────────────────────────────────────────────

  public function destroy(int $id): RedirectResponse
  {
    try {
      Employee::findOrFail($id)->delete();

      return redirect()->route('employees-index')->with('success', 'Employee has been deleted.');
    } catch (ModelNotFoundException) {
      return redirect()->route('employees-index')->with('error', 'Employee record not found.');
    } catch (Throwable $e) {
      Log::error('Employee delete failed', ['id' => $id, 'error' => $e->getMessage()]);

      return redirect()->route('employees-index')->with('error', 'Something went wrong while deleting the employee.');
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  HELPERS
  // ──────────────────────────────────────────────────────────────

  private function findWithRelations(int $id): Employee
  {
    return Employee::with([
      'permanentAddress',
      'currentAddress',
      'family',
      'children',
      'education.level',
      'governmentIds',
    ])->findOrFail($id);
  }


  public function facialRecognitionRegistration()
  {

   $employeesWithoutFace = Employee::where('status', 'inactive')
      ->get();

    return view('content.admin.employees-registration.facial-recognitation', compact('employeesWithoutFace'));
  }

  public function facialRecognitionSave(Request $request)
  {

    try {
     
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'descriptor'  => 'required|array|min:128' 
        ]);



        
        EmployeeFaceInfo::updateOrCreate(
            ['employee_id' => $request->employee_id],
            [
                'descriptor' => json_encode($request->descriptor),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Facial data enrolled successfully!'
        ]);

    } catch (\Exception $e) {
        // Log::error("Face Enrollment Error: " . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
  }
}
