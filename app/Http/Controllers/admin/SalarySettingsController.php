<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeRecurringPayrollItem;
use App\Models\EmployeeSalarySetting;
use App\Services\PayslipService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class SalarySettingsController extends Controller
{
  /**
   * All valid category codes that can be stored on a recurring item.
   * Kept in sync with the modal blade and PayslipService::DEDUCTION_CATEGORY_CODES.
   */
  private const RECURRING_CATEGORY_CODES = [
    'sss_salary_loan',
    'sss_calamity_loan',
    'sss_loan',
    'sss_mutual_fund',
    'hdmf_loan',
    'hdmf_calamity_loan',
    'pagibig_loan',
    'fb_real_estate_loan',
    'fb_vehicle_loan',
    'fb_appliance_loan',
    'fb_medical_loan',
    'fb_medical_loan_2',
    'fb_personal',
    'fb_motorcycle',
    'fb_appliance',
    'fb_vehicle',
    'fb_medical_1',
    'fb_medical_2',
    'fb_educational',
    'housing_loan',
    'accounts_receivable',
  ];

  public function __construct(private readonly PayslipService $payslipService) {}

  // ─────────────────────────────────────────────────────────────────────────
  // Public actions
  // ─────────────────────────────────────────────────────────────────────────

  public function index(Request $request): View
  {
    $employees = $this->activeEmployees();

    $selectedEmployeeId = $request->filled('employee_id')
      ? (int) $request->input('employee_id')
      : null;

    $selectedEmployee = $selectedEmployeeId
      ? $employees->firstWhere('id', $selectedEmployeeId)
      : null;

    $salary         = null;
    $recurringItems = collect();

    if ($selectedEmployee) {
      $salary         = $this->getActiveSalarySetting($selectedEmployee->id);
      $recurringItems = $this->loadRecurringItemsForEmployee($selectedEmployee->id);
    }

    return view('content.admin.payrolls.salary-settings', [
      'employees'        => $employees,
      'selectedEmployee' => $selectedEmployee,
      'salary'           => $salary,
      'recurringItems'   => $recurringItems,
      'salaryConfig'     => [
        'routes' => [
          'show'  => route('salary-settings.show', ['employee' => '__EMPLOYEE__']),
          'store' => route('salary-settings.store'),
          'csrf'  => csrf_token(),
        ],
      ],
    ]);
  }

  public function show(Employee $employee): JsonResponse
  {
    $employee->loadMissing(['office:id,office_name', 'position:id,position_name']);

    $salary         = $this->getActiveSalarySetting($employee->id);
    $recurringItems = $this->loadRecurringItemsForEmployee($employee->id);

    return response()->json([
      'success' => true,
      'data'    => [
        'employee' => [
          'id'              => $employee->id,
          'employee_number' => $employee->employee_number,
          'full_name'       => $employee->full_name,
          'department'      => $employee->office?->office_name ?? '—',
          'position'        => $employee->position?->position_name ?? '—',
          'status'          => $employee->status,
          'date_hired'      => $employee->created_at?->format('M d, Y') ?? '—',
          'avatar'          => strtoupper(
            substr($employee->first_name ?? '', 0, 1) .
              substr($employee->last_name  ?? '', 0, 1)
          ),
        ],
        'salary' => $salary ? [
          'id'                => $salary->id,
          'monthly_rate'      => (float) $salary->monthly_rate,
          'semi_monthly_rate' => (float) $salary->semi_monthly_rate,
          'daily_rate'        => (float) $salary->daily_rate,
          'payroll_type'      => $salary->payroll_type,
          'payment_frequency' => $salary->payment_frequency,
          'effective_date'    => optional($salary->effective_date)->format('Y-m-d'),
          'is_active'         => (bool) $salary->is_active,
          'remarks'           => $salary->remarks,
        ] : null,
        'recurring_items' => $recurringItems->values(),
      ],
    ]);
  }

  public function store(Request $request): JsonResponse
  {
    return $this->upsert($request->all());
  }

  public function update(Request $request, Employee $employee): JsonResponse
  {
    // Lock employee_id to route-model-bound employee to prevent payload tampering.
    return $this->upsert(
      array_merge($request->all(), ['employee_id' => $employee->id]),
      'Salary settings updated successfully.',
    );
  }

  public function destroyRecurringItem(EmployeeRecurringPayrollItem $recurringItem): JsonResponse
  {
    try {
      $recurringItem->delete();

      return response()->json([
        'success' => true,
        'message' => 'Recurring item deleted successfully.',
      ]);
    } catch (Throwable $e) {
      Log::error('Recurring item delete failed.', [
        'recurring_item_id' => $recurringItem->id,
        'exception'         => $e->getMessage(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to delete recurring item.',
      ], 500);
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private — upsert
  // ─────────────────────────────────────────────────────────────────────────

  private function upsert(
    array  $payload,
    string $successMessage = 'Salary settings saved successfully.',
  ): JsonResponse {
    $validator = Validator::make($payload, $this->rules(), $this->messages());

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Please check the highlighted fields.',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $validated = $validator->validated();

    try {
      $result = DB::transaction(function () use ($validated) {
        /** @var Employee $employee */
        $employee = Employee::query()->findOrFail($validated['employee_id']);

        // Deactivate prior settings that differ in effective_date.
        EmployeeSalarySetting::query()
          ->where('employee_id', $employee->id)
          ->where('is_active', true)
          ->where('effective_date', '!=', $validated['effective_date'])
          ->update(['is_active' => false]);

        $salary = EmployeeSalarySetting::query()->updateOrCreate(
          [
            'employee_id'    => $employee->id,
            'effective_date' => $validated['effective_date'],
          ],
          [
            'payroll_type'      => $this->normalizePayrollType($validated['payroll_type']),
            'payment_frequency' => $this->normalizePaymentFrequency($validated['payment_frequency']),
            'monthly_rate'      => $validated['monthly_rate'],
            'semi_monthly_rate' => $validated['semi_monthly_rate'],
            'daily_rate'        => $validated['daily_rate'],
            'is_active'         => (bool) ($validated['is_active'] ?? true),
            'remarks'           => $validated['remarks'] ?? null,
          ],
        );

        $recurringItems = $this->syncRecurringItems(
          employeeId: $employee->id,
          rows: $validated['recurring_items'] ?? [],
        );

        return compact('employee', 'salary', 'recurringItems');
      });

      return response()->json([
        'success' => true,
        'message' => $successMessage,
        'data'    => [
          'employee_id'           => $result['employee']->id,
          'salary_setting_id'     => $result['salary']->id,
          'recurring_items_count' => $result['recurringItems']->count(),
        ],
      ]);
    } catch (Throwable $e) {
      Log::error('Salary settings save failed.', [
        'payload'   => $validated ?? $payload,
        'exception' => $e->getMessage(),
        'trace'     => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'An unexpected error occurred while saving salary settings.',
      ], 500);
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private — data loading
  // ─────────────────────────────────────────────────────────────────────────

  private function activeEmployees(): Collection
  {
    return Employee::query()
      ->with(['office:id,office_name', 'position:id,position_name'])
      ->where('status', 'active')
      ->orderBy('last_name')
      ->orderBy('first_name')
      ->get();
  }

  private function getActiveSalarySetting(int $employeeId): ?EmployeeSalarySetting
  {
    return EmployeeSalarySetting::query()
      ->where('employee_id', $employeeId)
      ->where('is_active', true)
      ->latest('effective_date')
      ->latest('id')
      ->first();
  }

  private function loadRecurringItemsForEmployee(int $employeeId): Collection
  {
    return EmployeeRecurringPayrollItem::query()
      ->where('employee_id', $employeeId)
      ->orderByDesc('is_active')
      ->orderBy('id')
      ->get()
      ->transform(function (EmployeeRecurringPayrollItem $item): object {
        $applyOn = $this->humanTiming($item->application_timing);

        return (object) [
          'id'             => $item->id,
          'type'           => $item->type ?? 'Earning',
          'category_code'  => $item->category_code ?? '',
          'category_label' => $this->payslipService->humanizeCategoryCode(
            (string) ($item->category_code ?? '')
          ),
          'amount'   => (float) $item->amount,
          'apply_on' => $applyOn,
          'applyOn'  => $applyOn,         // JS-friendly alias
          'is_active' => (bool) $item->is_active,
          'active'    => (bool) $item->is_active, // JS-friendly alias
        ];
      });
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Private — recurring item sync
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Sync recurring items for an employee.
   *
   * Algorithm:
   *   1. Upsert every row in $rows (create if no valid id, update otherwise).
   *   2. Delete any DB rows NOT present in $rows.
   *      When $rows is empty, deletes all existing rows for the employee.
   */
  private function syncRecurringItems(int $employeeId, array $rows): Collection
  {
    $persistedIds = [];

    foreach ($rows as $row) {
      $incomingId = isset($row['id']) && is_numeric($row['id']) && (int) $row['id'] > 0
        ? (int) $row['id']
        : null;

      $attributes = [
        'employee_id'        => $employeeId,
        'type'               => (string) ($row['type'] ?? 'Earning'),
        'category_code'      => (string) ($row['category_code'] ?? ''),
        'amount'             => (float) ($row['amount'] ?? 0),
        'application_timing' => $this->mapTimingToDatabase((string) ($row['applyOn'] ?? 'Both Cutoffs')),
        'is_active'          => (bool) ($row['active'] ?? true),
        'notes'              => null,
      ];

      $record = $incomingId
        ? EmployeeRecurringPayrollItem::query()
        ->where('employee_id', $employeeId)
        ->where('id', $incomingId)
        ->first()
        : null;

      if ($record) {
        $record->update($attributes);
      } else {
        $record = EmployeeRecurringPayrollItem::query()->create($attributes);
      }

      $persistedIds[] = $record->id;
    }

    // Delete rows no longer in the submitted payload.
    EmployeeRecurringPayrollItem::query()
      ->where('employee_id', $employeeId)
      ->when(
        ! empty($persistedIds),
        fn($q) => $q->whereNotIn('id', $persistedIds),
        fn($q) => $q->whereNotNull('id'),  // delete all when payload is empty
      )
      ->delete();

    return EmployeeRecurringPayrollItem::query()
      ->where('employee_id', $employeeId)
      ->orderByDesc('is_active')
      ->orderBy('id')
      ->get();
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private — validation rules
  // ─────────────────────────────────────────────────────────────────────────

  private function rules(): array
  {
    return [
      'employee_id'       => ['required', 'integer', 'exists:employees,id'],
      'monthly_rate'      => ['required', 'numeric', 'min:0.01'],
      'semi_monthly_rate' => ['required', 'numeric', 'min:0'],
      'daily_rate'        => ['required', 'numeric', 'min:0'],

      'payroll_type' => [
        'required',
        'string',
        Rule::in(['semi_monthly', 'semi-monthly']),
      ],
      'payment_frequency' => [
        'required',
        'string',
        Rule::in(['every_cutoff', 'every-cutoff']),
      ],

      'effective_date' => ['required', 'date'],
      'is_active'      => ['nullable', 'boolean'],
      'remarks'        => ['nullable', 'string', 'max:255'],

      'recurring_items'                  => ['nullable', 'array'],
      'recurring_items.*.id'             => ['nullable'],
      'recurring_items.*.type'           => ['required', 'string', Rule::in(['Earning', 'Deduction'])],
      'recurring_items.*.category_code'  => [
        'required',
        'string',
        Rule::in(self::RECURRING_CATEGORY_CODES),
      ],
      'recurring_items.*.amount'         => ['required', 'numeric', 'min:0.01'],
      'recurring_items.*.applyOn'        => [
        'required',
        'string',
        Rule::in(['Both Cutoffs', '1st Cutoff Only', '2nd Cutoff Only']),
      ],
      'recurring_items.*.active'         => ['nullable', 'boolean'],
    ];
  }

  private function messages(): array
  {
    return [
      'employee_id.required'                    => 'Please select an employee.',
      'employee_id.exists'                      => 'The selected employee is invalid.',
      'monthly_rate.required'                   => 'Monthly rate is required.',
      'monthly_rate.min'                        => 'Monthly rate must be greater than zero.',
      'effective_date.required'                 => 'Effective date is required.',
      'recurring_items.*.category_code.required' => 'Recurring item category is required.',
      'recurring_items.*.category_code.in'      => 'The selected recurring item category is invalid.',
      'recurring_items.*.amount.required'       => 'Recurring item amount is required.',
      'recurring_items.*.amount.min'            => 'Recurring item amount must be greater than zero.',
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private — mapping helpers
  // ─────────────────────────────────────────────────────────────────────────

  private function mapTimingToDatabase(string $timing): string
  {
    return match ($timing) {
      '1st Cutoff Only' => 'first_cutoff_only',
      '2nd Cutoff Only' => 'second_cutoff_only',
      default           => 'every_cutoff',
    };
  }

  private function humanTiming(?string $timing): string
  {
    return match ($timing) {
      'first_cutoff_only'  => '1st Cutoff Only',
      'second_cutoff_only' => '2nd Cutoff Only',
      default              => 'Both Cutoffs',
    };
  }

  private function normalizePayrollType(string $value): string
  {
    return $value === 'semi-monthly' ? 'semi_monthly' : $value;
  }

  private function normalizePaymentFrequency(string $value): string
  {
    return $value === 'every-cutoff' ? 'every_cutoff' : $value;
  }
}
