<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeChild;
use App\Models\EmployeeEducation;
use App\Models\EmployeeGovernmentId;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
      'citizenship'     => ['nullable', 'string', 'max:100'],
      'gender'          => ['nullable', Rule::in(Employee::GENDERS)],
      'date_of_birth'   => ['nullable', 'date'],
      'place_of_birth'  => ['nullable', 'string', 'max:150'],
      'mobile_number'   => ['nullable', 'string', 'max:20'],
      'landline_number' => ['nullable', 'string', 'max:20'],
      'civil_status'    => ['nullable', Rule::in(Employee::CIVIL_STATUSES)],
      'height_cm'       => ['nullable', 'numeric', 'min:0', 'max:300'],
      'weight_kg'       => ['nullable', 'numeric', 'min:0', 'max:500'],
      'blood_type'      => ['nullable', Rule::in(Employee::BLOOD_TYPES)],
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

      'family.father_name'              => ['nullable', 'string', 'max:150'],
      'family.mother_name'              => ['nullable', 'string', 'max:150'],
      'family.spouse_name'              => ['nullable', 'string', 'max:150'],
      'family.spouse_occupation'        => ['nullable', 'string', 'max:100'],
      'family.spouse_employer'          => ['nullable', 'string', 'max:150'],
      'family.spouse_business_address'  => ['nullable', 'string', 'max:255'],
      'family.emergency_contact_name'   => ['required', 'string', 'max:150'],
      'family.emergency_contact_number' => ['required', 'string', 'max:20'],
      'family.emergency_relationship'   => ['nullable', 'string', 'max:50'],

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
    if ($request->filled('permanent')) {
      $employee->permanentAddress()->updateOrCreate(
        ['employee_id' => $employee->id],
        $request->input('permanent')
      );
    }

    if ($request->filled('current')) {
      $employee->currentAddress()->updateOrCreate(
        ['employee_id' => $employee->id],
        $request->input('current')
      );
    }

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
    $employees = Employee::orderByDesc('created_at')->paginate(15);

    return view('content.admin.employees-registration.employees-registration', compact('employees'));
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
    // Validate before opening a transaction — ValidationException propagates naturally.
    $validated = $request->validate(
      array_merge($this->employeeRules(), $this->relatedRules())
    );

    try {
      DB::transaction(function () use ($request) {
        $employee = Employee::create($request->only([
          'employee_number',
          'first_name',
          'middle_name',
          'last_name',
          'citizenship',
          'gender',
          'date_of_birth',
          'place_of_birth',
          'mobile_number',
          'landline_number',
          'civil_status',
          'height_cm',
          'weight_kg',
          'blood_type',
        ]));

        $this->saveRelated($employee, $request);
      });

      return redirect()
        ->route('employees-index')
        ->with('success', 'Employee successfully registered.');
    } catch (Throwable $e) {
      Log::error('Employee store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

      return redirect()
        ->back()
        ->withInput()
        ->with('error', 'Something went wrong while saving the employee. Please try again.');
    }
  }

  // ──────────────────────────────────────────────────────────────
  //  SHOW
  // ──────────────────────────────────────────────────────────────

  public function show(int $id): mixed
  {
    try {
      $employee = $this->findWithRelations($id);

      return view('content.admin.employees.show', compact('employee'));
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

      return view('content.admin.employees.edit', compact('employee'));
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
          'landline_number',
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
}
