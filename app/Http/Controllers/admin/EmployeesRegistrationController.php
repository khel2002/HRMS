<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeChild;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFaceInfo;
use App\Models\EmployeeGovernmentId;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class EmployeesRegistrationController extends Controller
{


  // ── Validation rules ──────────────────────────────────────────────────────

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
      'position_id'     => ['required', 'integer', 'exists:employee_position,id'],
      'office_id'       => ['required', 'integer', 'exists:offices,id'],
    ];
  }

  private function relatedRules(): array
  {
    return [
      'permanent.house_number'          => ['nullable', 'string', 'max:50'],
      'permanent.street'                => ['nullable', 'string', 'max:100'],
      'permanent.subdivision'           => ['nullable', 'string', 'max:100'],
      'permanent.zip_code'              => ['nullable', 'string', 'max:20'],
      'permanent.region'                => ['nullable', 'string', 'max:150'],
      'permanent.region_code'           => ['nullable', 'string', 'max:20'],
      'permanent.province'              => ['nullable', 'string', 'max:150'],
      'permanent.province_code'         => ['nullable', 'string', 'max:20'],
      'permanent.city'                  => ['nullable', 'string', 'max:150'],
      'permanent.city_code'             => ['nullable', 'string', 'max:20'],
      'permanent.barangay'              => ['nullable', 'string', 'max:150'],

      'current.house_number'            => ['nullable', 'string', 'max:50'],
      'current.street'                  => ['nullable', 'string', 'max:100'],
      'current.subdivision'             => ['nullable', 'string', 'max:100'],
      'current.zip_code'                => ['nullable', 'string', 'max:20'],
      'current.region'                  => ['nullable', 'string', 'max:150'],
      'current.region_code'             => ['nullable', 'string', 'max:20'],
      'current.province'                => ['nullable', 'string', 'max:150'],
      'current.province_code'           => ['nullable', 'string', 'max:20'],
      'current.city'                    => ['nullable', 'string', 'max:150'],
      'current.city_code'               => ['nullable', 'string', 'max:20'],
      'current.barangay'                => ['nullable', 'string', 'max:150'],

      'family.father_name'              => ['required', 'string', 'max:150'],
      'family.mother_name'              => ['required', 'string', 'max:150'],
      'family.spouse_name'              => ['nullable', 'string', 'max:150'],
      'family.spouse_occupation'        => ['nullable', 'string', 'max:100'],
      'family.spouse_employer'          => ['nullable', 'string', 'max:150'],
      'family.spouse_business_address'  => ['nullable', 'string', 'max:255'],
      'family.emergency_contact_name'   => ['required', 'string', 'max:150'],
      'family.emergency_contact_number' => ['required', 'string', 'max:20'],
      'family.emergency_relationship'   => ['required', 'string', 'max:50'],

      'children'                        => ['nullable', 'array'],
      'children.*.child_name'           => ['nullable', 'string', 'max:150'],
      'children.*.date_of_birth'        => ['nullable', 'date'],

      'education'                       => ['nullable', 'array'],
      'education.*.level_id'            => ['required', 'integer', 'between:1,5'],
      'education.*.school_name'         => ['nullable', 'string', 'max:255'],
      'education.*.degree_course'       => ['nullable', 'string', 'max:150'],
      'education.*.period_from'         => ['nullable', 'integer', 'min:1950', 'max:2099'],
      'education.*.period_to'           => ['nullable', 'integer', 'min:1950', 'max:2099'],
      'education.*.highest_level_units' => ['nullable', 'string', 'max:50'],
      'education.*.year_graduated'      => ['nullable', 'integer', 'min:1950', 'max:2099'],
      'education.*.scholarship_honors'  => ['nullable', 'string', 'max:255'],

      'gov_ids'                         => ['nullable', 'array'],
      'gov_ids.*.name'                  => ['nullable', 'string', 'max:255'],
      'gov_ids.*.id_number'             => ['nullable', 'string', 'max:100'],
    ];
  }

  private function employeeFields(): array
  {
    return [
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
      'position_id',
      'office_id',
    ];
  }

  // ── Resolve encrypted ID ──────────────────────────────────────────────────

  private function resolveEmployee(string $encryptedId, bool $withRelations = false): Employee|RedirectResponse
  {
    try {
      $id = (int) Crypt::decryptString($encryptedId);
    } catch (DecryptException) {
      return redirect()->route('employees-index')->with('error', 'Employee record not found.');
    }

    try {
      return $withRelations ? $this->findWithRelations($id) : Employee::findOrFail($id);
    } catch (ModelNotFoundException) {
      return redirect()->route('employees-index')->with('error', 'Employee record not found.');
    }
  }

  private function findWithRelations(int $id): Employee
  {
    return Employee::with([
      'permanentAddress',
      'currentAddress',
      'family',
      'children',
      'education.level',
      'governmentIds',
      'position',
      'office',
    ])->findOrFail($id);
  }

  // ── Persist related models ────────────────────────────────────────────────

  private function saveRelated(Employee $employee, Request $request): void
  {
    $this->upsertAddress($employee->permanentAddress(), $employee->id, $request->input('permanent', []));
    $this->upsertAddress($employee->currentAddress(),  $employee->id, $request->input('current', []));

    // Strip any accidental employee_id key that would conflict with the match clause
    $familyData = collect($request->input('family', []))->except('employee_id')->all();

    if (! empty($familyData)) {
      $employee->family()->updateOrCreate(
        ['employee_id' => $employee->id],
        $familyData
      );
    }

    $this->syncHasMany(
      relation: $employee->children(),
      model: EmployeeChild::class,
      rows: collect($request->input('children', []))
        ->filter(fn($c) => ! empty($c['child_name']))
        ->map(fn($c) => [
          'employee_id'   => $employee->id,
          'child_name'    => $c['child_name'],
          'date_of_birth' => $c['date_of_birth'] ?? null,
        ])
        ->values()->all()
    );

    $this->syncHasMany(
      relation: $employee->education(),
      model: EmployeeEducation::class,
      rows: collect($request->input('education', []))
        ->filter(fn($e) => ! empty($e['school_name']))
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
        ->values()->all()
    );

    $this->syncHasMany(
      relation: $employee->governmentIds(),
      model: EmployeeGovernmentId::class,
      rows: collect($request->input('gov_ids', []))
        ->filter(fn($g) => ! empty($g['name']))
        ->map(fn($g) => [
          'employee_id' => $employee->id,
          'name'        => $g['name'],
          'id_number'   => $g['id_number'] ?? null,
        ])
        ->values()->all()
    );
  }

  private function upsertAddress(object $relation, int $employeeId, array $raw): void
  {
    $relation->updateOrCreate(
      ['employee_id' => $employeeId],
      [
        'house_number' => $raw['house_number'] ?? '',
        'street'       => $raw['street']       ?? '',
        'subdivision'  => $raw['subdivision']  ?? '',
        'barangay'     => $raw['barangay']     ?? '',
        'city'         => $raw['city']         ?? '',
        'province'     => $raw['province']     ?? '',
        'zip_code'     => $raw['zip_code']     ?? '',
      ]
    );
  }

  private function syncHasMany(object $relation, string $model, array $rows): void
  {
    $relation->delete();

    if (! empty($rows)) {
      $model::insert($rows);
    }
  }

  // ── SEARCH ────────────────────────────────────────────────────────────────

  public function search(Request $request): JsonResponse
  {
    $q = trim($request->input('q', ''));

    if (strlen($q) < 2) {
      return response()->json([]);
    }

    $employees = Employee::with(['position', 'office'])
      ->whereNull('deleted_at')
      ->where(function ($query) use ($q) {
        $query->where('employee_number', 'like', "%{$q}%")
          ->orWhere('first_name',  'like', "%{$q}%")
          ->orWhere('last_name',   'like', "%{$q}%")
          ->orWhere('middle_name', 'like', "%{$q}%")
          ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$q}%"]);
      })
      ->orderBy('last_name')
      ->orderBy('first_name')
      ->limit(15)
      ->get()
      ->map(fn($e) => [
        'id'              => $e->id,
        'employee_number' => $e->employee_number,
        'first_name'      => $e->first_name,
        'middle_name'     => $e->middle_name,
        'last_name'       => $e->last_name,
        'position_id'     => $e->position_id,
        'position_name'   => $e->position?->position_name ?? '—',
        'office_id'       => $e->office_id,
        'office_name'     => $e->office?->office_name ?? '—',
      ]);

    return response()->json($employees);
  }

  // ── INDEX / CREATE ────────────────────────────────────────────────────────

  public function index()
  {
    return view('content.admin.employees-registration.employees-registration');
  }

  public function create()
  {
    return view('content.admin.employees-registration.employees-registration');
  }

  // ── STORE ─────────────────────────────────────────────────────────────────

  public function store(Request $request): RedirectResponse
  {
    $validator = Validator::make(
      $request->all(),
      array_merge($this->employeeRules(), $this->relatedRules())
    );

    if ($validator->fails()) {
      Log::warning('Employee store validation failed', ['errors' => $validator->errors()->toArray()]);

      return redirect()->back()->withErrors($validator)->withInput();
    }

    try {
      DB::transaction(function () use ($request) {
        $employee = Employee::create(array_merge(
          $request->only($this->employeeFields()),
          ['status' => 'inactive']
        ));

        $this->saveRelated($employee, $request);
      });

      return redirect()->route('employees-index')->with('success', 'Employee successfully registered.');
    } catch (Throwable $e) {
      Log::error('Employee store failed', [
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
      ]);

      return redirect()->back()->withInput()->with('error', 'DB error: ' . $e->getMessage());
    }
  }

  // ── SHOW ──────────────────────────────────────────────────────────────────

  public function show(string $encryptedId): mixed
  {
    $employee = $this->resolveEmployee($encryptedId, withRelations: true);

    if ($employee instanceof RedirectResponse) {
      return $employee;
    }

    return view('content.admin.employees-registration.view-employee', compact('employee'));
  }

  // ── EDIT ──────────────────────────────────────────────────────────────────

  public function edit(string $encryptedId): mixed
  {
    $employee = $this->resolveEmployee($encryptedId, withRelations: true);

    if ($employee instanceof RedirectResponse) {
      return $employee;
    }

    return view('content.admin.employees-registration.edit-employee', compact('employee'));
  }

  // ── UPDATE ────────────────────────────────────────────────────────────────

  public function update(Request $request, string $encryptedId): RedirectResponse
  {
    $employee = $this->resolveEmployee($encryptedId);

    if ($employee instanceof RedirectResponse) {
      return $employee;
    }

    $validator = Validator::make(
      $request->all(),
      array_merge($this->employeeRules($employee->id), $this->relatedRules())
    );

    if ($validator->fails()) {
      Log::warning('Employee update validation failed', [
        'id'     => $employee->id,
        'errors' => $validator->errors()->toArray(),
      ]);

      return redirect()->back()->withErrors($validator)->withInput();
    }

    try {
      DB::transaction(function () use ($request, $employee) {
        $employee->update($request->only($this->employeeFields()));
        $this->saveRelated($employee, $request);
      });

      return redirect()
        ->route('employee-show', Crypt::encryptString($employee->id))
        ->with('success', 'Employee record updated successfully.');
    } catch (Throwable $e) {
      Log::error('Employee update failed', [
        'id'    => $employee->id,
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      // !! TEMPORARY — exposes the real exception so you can diagnose it.
      // Once fixed, replace the with('error', ...) value with a generic message.
      return redirect()->back()->withInput()
        ->with('error', '[DEBUG] ' . $e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine());
    }
  }

  // ── DESTROY ───────────────────────────────────────────────────────────────

  public function destroy(string $encryptedId): RedirectResponse
  {
    $employee = $this->resolveEmployee($encryptedId);

    if ($employee instanceof RedirectResponse) {
      return $employee;
    }

    try {
      $employee->delete();

      return redirect()->route('employees-index')->with('success', 'Employee has been deleted.');
    } catch (Throwable $e) {
      Log::error('Employee delete failed', ['id' => $employee->id, 'error' => $e->getMessage()]);

      return redirect()->route('employees-index')->with('error', 'Something went wrong while deleting the employee.');
    }
  }

  // ── FACIAL RECOGNITION ────────────────────────────────────────────────────

  public function facialRecognitionRegistration()
  {
    $employeesWithoutFace = Employee::where('status', 'inactive')->get();

    return view('content.admin.employees-registration.facial-recognitation', compact('employeesWithoutFace'));
  }

  public function facialRecognitionSave(Request $request)
  {
    try {
      $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'descriptor'  => 'required|array|min:128',
      ]);

      EmployeeFaceInfo::updateOrCreate(
        ['employee_id' => $request->employee_id],
        ['descriptor'  => json_encode($request->descriptor)]
      );

      return response()->json(['status' => 'success', 'message' => 'Facial data enrolled successfully!']);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }
}
