<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Mail\PayslipMail;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Pdfs\PayslipPdf;
use App\Services\PayslipService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class PaySlipController extends Controller
{
  public function __construct(
    private readonly PayslipService $service,
    private readonly PayslipPdf     $pdf,
  ) {}

  // ─────────────────────────────────────────────────────────────────────────
  // Public actions
  // ─────────────────────────────────────────────────────────────────────────

  public function index(Request $request)
  {
    [$canSelectEmployee, $employees] = $this->resolveEmployeeScope($request);

    $selectedEmployee = $this->resolveSelectedEmployee($request, $employees, $canSelectEmployee);

    $year  = (int) ($request->input('year')  ?? now()->year);
    $month = (int) ($request->input('month') ?? now()->month);
    $month = max(1, min(12, $month));

    $payslipData = null;
    if ($selectedEmployee) {
      [$first, $second] = $this->loadPayrollsForMonth($selectedEmployee->id, $year, $month);
      $payslipData = $this->buildViewData($selectedEmployee, $first, $second, $year, $month);
    }

    return view('content.admin.payrolls.payslip', [
      'employees'         => $employees,
      'selectedEmployee'  => $selectedEmployee,
      'year'              => $year,
      'month'             => $month,
      'payslipData'       => $payslipData,
      'leaveBalances'     => $selectedEmployee
        ? LeaveBalance::query()
        ->with('leaveType:id,name')
        ->where('employee_id', $selectedEmployee->id)
        ->where('year', $year)
        ->orderBy('leave_type_id')
        ->get()
        : collect(),
      'canSelectEmployee' => $canSelectEmployee,
      'payslipRoutes'     => $this->payslipRoutes($request),
    ]);
  }

  public function process(Request $request)
  {
    [$canSelectEmployee] = $this->resolveEmployeeScope($request);

    $validated = $request->validate([
      'employee_id' => $canSelectEmployee
        ? ['required', 'integer', 'exists:employees,id']
        : ['nullable'],
      'year'  => ['required', 'integer', 'min:2000', 'max:2099'],
      'month' => ['required', 'integer', 'min:1', 'max:12'],
    ]);

    $employeeId = $this->resolveEmployeeIdFromValidated($validated, $request->user(), $canSelectEmployee);

    try {
      $this->service->processMonth(
        $employeeId,
        (int) $validated['year'],
        (int) $validated['month'],
        (int) $request->user()->id,
      );
    } catch (RuntimeException $e) {
      return back()->withErrors(['payslip' => $e->getMessage()]);
    }

    return redirect()
      ->route($this->payslipRouteName($request, 'index'), array_filter([
        'employee_id' => $canSelectEmployee ? $employeeId : null,
        'year'        => (int) $validated['year'],
        'month'       => (int) $validated['month'],
      ]))
      ->with('success', 'Payslip processed successfully.');
  }

  public function pdf(Request $request)
  {
    [$canSelectEmployee] = $this->resolveEmployeeScope($request);

    $validated = $request->validate([
      'employee_id' => $canSelectEmployee
        ? ['required', 'integer', 'exists:employees,id']
        : ['nullable'],
      'year'  => ['required', 'integer', 'min:2000', 'max:2099'],
      'month' => ['required', 'integer', 'min:1', 'max:12'],
    ]);

    $employeeId = $this->resolveEmployeeIdFromValidated($validated, $request->user(), $canSelectEmployee);

    $employee = Employee::query()
      ->with(['office:id,office_name', 'position:id,position_name'])
      ->findOrFail($employeeId);

    [$first, $second] = $this->loadPayrollsForMonth($employeeId, (int) $validated['year'], (int) $validated['month']);

    // Auto-compute if not yet processed.
    if (! $first || ! $second) {
      try {
        $this->service->processMonth(
          $employeeId,
          (int) $validated['year'],
          (int) $validated['month'],
          (int) $request->user()->id,
        );
      } catch (RuntimeException $e) {
        return back()->withErrors(['payslip' => $e->getMessage()]);
      }

      [$first, $second] = $this->loadPayrollsForMonth($employeeId, (int) $validated['year'], (int) $validated['month']);
    }

    return $this->pdf->generate($employee, $first, $second, [
      'year'  => (int) $validated['year'],
      'month' => (int) $validated['month'],
    ], $request->boolean('download'));
  }

  public function email(Request $request): JsonResponse
  {
    [$canSelectEmployee] = $this->resolveEmployeeScope($request);

    $validated = $request->validate([
      'employee_id' => $canSelectEmployee
        ? ['required', 'integer', 'exists:employees,id']
        : ['nullable'],
      'year'    => ['required', 'integer', 'min:2000', 'max:2099'],
      'month'   => ['required', 'integer', 'min:1', 'max:12'],
      'subject' => ['required', 'string', 'max:150'],
      'message' => ['nullable', 'string', 'max:2000'],
    ]);

    $employeeId = $this->resolveEmployeeIdFromValidated($validated, $request->user(), $canSelectEmployee);

    $employee = Employee::query()
      ->with(['office:id,office_name', 'position:id,position_name'])
      ->findOrFail($employeeId);

    if (! $employee->email) {
      return response()->json(['message' => 'Employee email is not set.'], 422);
    }

    // Ensure payrolls exist; processMonth is idempotent for non-locked payrolls.
    try {
      $this->service->processMonth(
        $employeeId,
        (int) $validated['year'],
        (int) $validated['month'],
        (int) $request->user()->id,
      );
    } catch (RuntimeException $e) {
      return response()->json(['message' => $e->getMessage()], 422);
    }

    [$first, $second] = $this->loadPayrollsForMonth($employeeId, (int) $validated['year'], (int) $validated['month']);

    if (! $first && ! $second) {
      return response()->json(['message' => 'No payroll data found for this period.'], 422);
    }

    [$filename, $binary] = $this->pdf->renderBinary($employee, $first, $second, [
      'year'  => (int) $validated['year'],
      'month' => (int) $validated['month'],
    ]);

    Mail::to($employee->email)->send(new PayslipMail(
      subjectLine: $validated['subject'],
      messageBody: trim((string) ($validated['message'] ?? 'Please see your attached payslip.')),
      filename: $filename,
      pdfBinary: $binary,
    ));

    return response()->json([
      'success' => true,
      'message' => 'Payslip emailed to ' . $employee->email . '.',
    ]);
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers — employee resolution
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * @return array{0: bool, 1: \Illuminate\Support\Collection}
   */
  private function resolveEmployeeScope(Request $request): array
  {
    $roleName          = $request->user()?->role?->name ?? '';
    $canSelectEmployee = in_array($roleName, ['Admin', 'HR'], true);

    $employees = collect();
    if ($canSelectEmployee) {
      $employees = Employee::query()
        ->with(['office:id,office_name', 'position:id,position_name'])
        ->where('status', 'active')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();
    }

    return [$canSelectEmployee, $employees];
  }

  private function resolveSelectedEmployee(
    Request $request,
    $employees,
    bool $canSelectEmployee,
  ): ?Employee {
    if (! $canSelectEmployee) {
      $empId = (int) ($request->user()?->employee_id ?? 0);
      if (! $empId) {
        return null;
      }

      return Employee::query()
        ->with(['office:id,office_name', 'position:id,position_name'])
        ->find($empId);
    }

    $selectedId = $request->filled('employee_id')
      ? (int) $request->input('employee_id')
      : null;

    return $selectedId ? $employees->firstWhere('id', $selectedId) : null;
  }

  private function resolveEmployeeIdFromValidated(array $validated, $user, bool $canSelectEmployee): int
  {
    if ($canSelectEmployee) {
      return (int) ($validated['employee_id'] ?? 0);
    }

    $empId = (int) ($user?->employee_id ?? 0);
    if (! $empId) {
      abort(403, 'Employee profile not found.');
    }

    return $empId;
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers — payroll loading
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * @return array{0: ?Payroll, 1: ?Payroll}
   */
  private function loadPayrollsForMonth(int $employeeId, int $year, int $month): array
  {
    $periods = PayrollPeriod::query()
      ->where('year', $year)
      ->where('month', $month)
      ->whereIn('cutoff_type', ['first_cutoff', 'second_cutoff'])
      ->get()
      ->keyBy('cutoff_type');

    $firstPeriodId  = $periods->get('first_cutoff')?->id;
    $secondPeriodId = $periods->get('second_cutoff')?->id;

    $periodIds = array_values(array_filter([$firstPeriodId, $secondPeriodId]));

    if (empty($periodIds)) {
      return [null, null];
    }

    $payrolls = Payroll::query()
      ->with(['payrollPeriod', 'items.payrollItemType'])
      ->where('employee_id', $employeeId)
      ->whereIn('payroll_period_id', $periodIds)
      ->get()
      ->keyBy(fn(Payroll $p) => $p->payrollPeriod?->cutoff_type ?? '');

    return [
      $payrolls->get('first_cutoff'),
      $payrolls->get('second_cutoff'),
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private helpers — view data builder
  // ─────────────────────────────────────────────────────────────────────────

  private function buildViewData(
    Employee  $employee,
    ?Payroll  $first,
    ?Payroll  $second,
    int       $year,
    int       $month,
	  ): array {
	    $monthStart  = Carbon::create($year, $month, 1);
	    $periodLabel = $monthStart->format('F Y');

	    // Overall payslip period (both cutoffs combined).
	    // New rule: 26th (prev month) → 25th (current month)
	    $computedFrom = $monthStart->copy()->subMonthNoOverflow()->day(26);
	    $computedTo   = $monthStart->copy()->day(25);

	    $monthRange = $computedFrom->format('M d') . ' – ' . $computedTo->format('M d, Y');
	    if ($first?->payrollPeriod && $second?->payrollPeriod) {
	      $monthRange = $first->payrollPeriod->date_from->format('M d')
	        . ' – '
	        . $second->payrollPeriod->date_to->format('M d, Y');
	    }

    $firstItems  = $first?->items  ?? collect();
    $secondItems = $second?->items ?? collect();

    $group = static fn($items, string $category) => $items
      ->filter(fn($i) => ($i->category ?? '') === $category)
      ->sortBy('sort_order')
      ->values();

    $gross1 = (float) ($first?->gross_pay         ?? 0);
    $ded1   = (float) ($first?->total_deductions  ?? 0);
    $net1   = (float) ($first?->net_pay           ?? 0);

    $gross2 = (float) ($second?->gross_pay        ?? 0);
    $ded2   = (float) ($second?->total_deductions ?? 0);
    $net2   = (float) ($second?->net_pay          ?? 0);

    return [
      'periodLabel' => $periodLabel,
      'monthRange'  => $monthRange,
      'month'       => $month,
      'year'        => $year,
      'hasPayroll'  => (bool) ($first || $second),
      'first'       => [
        'periodRange'      => $first?->payrollPeriod
          ? $first->payrollPeriod->date_from->format('M d')
          . ' – '
          . $first->payrollPeriod->date_to->format('M d, Y')
          : null,
        'status'           => $first?->status,
        'earnings'         => $group($firstItems, 'earning'),
        'deductions'       => $group($firstItems, 'deduction'),
        'gross'            => $gross1,
        'deductions_total' => $ded1,
        'net'              => $net1,
      ],
      'second' => [
        'periodRange'      => $second?->payrollPeriod
          ? $second->payrollPeriod->date_from->format('M d')
          . ' – '
          . $second->payrollPeriod->date_to->format('M d, Y')
          : null,
        'status'           => $second?->status,
        'earnings'         => $group($secondItems, 'earning'),
        'deductions'       => $group($secondItems, 'deduction'),
        'gross'            => $gross2,
        'deductions_total' => $ded2,
        'net'              => $net2,
      ],
      'combined' => [
        'gross'            => $gross1 + $gross2,
        'deductions_total' => $ded1   + $ded2,
        'net'              => $net1   + $net2,
      ],
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private helpers — route resolution
  // ─────────────────────────────────────────────────────────────────────────

  private function payslipRouteName(Request $request, string $action): string
  {
    return $this->payslipRouteBase($request) . '.' . $action;
  }

  private function payslipRouteBase(Request $request): string
  {
    $current = (string) ($request->route()?->getName() ?? '');

    foreach (['hr.payslip', 'employees.payslip', 'admin.payslip', 'payslip'] as $base) {
      if ($current === $base || Str::startsWith($current, $base . '.')) {
        return $base;
      }
    }

    // Fallback: derive from the route name prefix.
    if (Str::contains($current, 'payslip')) {
      return 'payslip';
    }

    return 'payslip';
  }

  private function payslipRoutes(Request $request): array
  {
    $base = $this->payslipRouteBase($request);

    return [
      'index'    => route($base . '.index'),
      'process'  => route($base . '.process'),
      'pdf'      => route($base . '.pdf'),
      'email'    => route($base . '.email'),
      'baseName' => $base,
    ];
  }
}
