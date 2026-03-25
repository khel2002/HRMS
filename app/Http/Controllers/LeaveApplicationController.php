<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaveApplicationController extends Controller
{
  // ── Web route ─────────────────────────────────────────────────────────────

  public function index()
  {
    return view('content.applications.leave-application');
  }

  // ── API: GET /admin/api/employees/{id}/leave-balances ─────────────────────
  //
  // Returns the employee's office and remaining leave balances for the three
  // trackable leave types (Vacation, Sick, SIL) for the current year.
  //
  // Balance rules applied here (not enforced, just surfaced for the UI):
  //   Vacation Leave  — 5 days/year, resets every year, no carry-over
  //   Sick Leave      — 5 days/year, unused carries over (max 14 total)
  //   SIL             — 5 days/year, mandatory use-within-year, resets to 0
  //
  // If no leave_balances row exists for a type, we return the default
  // allocation so the form is always usable even before HR seeds balances.

  public function balances(int $employeeId): JsonResponse
  {
    $employee = Employee::with(['office', 'position'])
      ->findOrFail($employeeId);

    $year = now()->year;

    // The three leave types that have trackable balances
    $trackedSlugs = [
      'vacation' => 'Vacation Leave',
      'sick'     => 'Sick Leave',
      'sil'      => 'Service Incentive Leave',
    ];

    // Defaults when no leave_balances row exists yet
    $defaults = [
      'vacation' => ['total' => 5,  'policy' => '5 days/year — resets annually'],
      'sick'     => ['total' => 5,  'policy' => 'Unused credits carry over (max 14 days)'],
      'sil'      => ['total' => 5,  'policy' => 'Must be used within the year — resets to 0'],
    ];

    // Fetch all leave types in one query
    $leaveTypes = LeaveType::whereIn('name', array_values($trackedSlugs))
      ->get()
      ->keyBy('name');

    // Fetch all existing balance rows for this employee + year in one query
    $leaveTypeIds = $leaveTypes->pluck('id')->all();

    $balanceRows = LeaveBalance::where('employee_id', $employeeId)
      ->where('year', $year)
      ->whereIn('leave_type_id', $leaveTypeIds)
      ->get()
      ->keyBy('leave_type_id');

    $balances = [];

    foreach ($trackedSlugs as $slug => $dbName) {
      $leaveType = $leaveTypes->get($dbName);

      if (! $leaveType) {
        continue; // leave type not seeded — skip silently
      }

      $row = $balanceRows->get($leaveType->id);

      // ── Apply policy rules ─────────────────────────────────────────────
      //
      //  Vacation Leave  — 5 days/year, resets every year (no carry-over)
      //  Service Incentive Leave — 5 days/year, must use within year (resets to 0)
      //  Sick Leave      — 5 days/year, unused carries over, max 14 accumulated
      //
      // If no DB row exists we use the default allocation for the year.
      // The remaining_days stored in DB is authoritative; we just cap SL at 14.

      $total     = $row?->total_days     ?? $defaults[$slug]['total'];
      $used      = $row?->used_days       ?? 0;
      $remaining = $row?->remaining_days  ?? $defaults[$slug]['total'];

      // Enforce Sick Leave carry-over cap: remaining can never exceed 14
      if ($slug === 'sick') {
        $remaining = min($remaining, 14);
        $total     = min($total, 14); // display total respects the cap too
      }

      // VL and SIL: remaining cannot exceed the annual allocation (5)
      if ($slug === 'vacation' || $slug === 'sil') {
        $remaining = min($remaining, 5);
        $total     = 5; // always show 5 as the annual total
      }

      $balances[] = [
        'slug'           => $slug,
        'name'           => $dbName,
        'total_days'     => $total,
        'used_days'      => $used,
        'remaining_days' => max(0, $remaining),
        'policy'         => $defaults[$slug]['policy'],
      ];
    }

    return response()->json([
      'employee_id'   => $employee->id,
      'office_name'   => $employee->office?->office_name ?? '—',
      'position_name' => $employee->position?->position_name ?? '—',
      'year'          => $year,
      'balances'      => $balances,
    ]);
  }

  // ── POST /admin/leave-application ─────────────────────────────────────────
  //
  // One form submission may carry multiple leave_type[] values.
  // One LeaveApplication row is created per selected leave type.
  //
  // Form field → DB column mapping:
  //   employee_id      → employee_id
  //   leave_type[]     → leave_type_id  (slug resolved to ID via lookup table)
  //   date_of_filing   → date_filed
  //   whole_day_from   → start_date
  //   whole_day_to     → end_date
  //   total_days       → total_days
  //   leave_details    → cause
  //   commutation      → appended to cause (no dedicated column)
  //   manager_status   → default 'pending'
  //   remarks          → default 'pending'
  //
  // Office context is resolved through the employee relationship — there is
  // no office_id column on leave_applications.

  public function store(Request $request): JsonResponse
  {
    // ── 1. Validate input ──────────────────────────────────────────────────

    $validated = $request->validate([
      'employee_id'        => ['required', 'integer', 'exists:employees,id'],
      'leave_type'         => ['required', 'array', 'min:1'],
      'leave_type.*'       => ['required', 'string'],
      'date_of_filing'     => ['required', 'date'],
      'whole_day_from'     => ['nullable', 'date'],
      'whole_day_to'       => ['nullable', 'date', 'gte:whole_day_from'],
      'half_day'           => ['nullable', 'array'],
      'half_day.*.date'    => ['required_with:half_day', 'date'],
      'half_day.*.session' => ['required_with:half_day', 'in:AM,PM'],
      'total_days'         => ['required', 'numeric', 'min:0.5'],
      'leave_details'      => ['nullable', 'string', 'max:500'],
      'commutation'        => ['nullable', 'in:requested,not_requested'],
      'salary'             => ['nullable', 'string', 'max:50'],
    ], [
      'employee_id.required' => 'Please search and select an employee.',
      'employee_id.exists'   => 'The selected employee no longer exists.',
      'leave_type.required'  => 'Please select at least one leave type.',
      'leave_type.min'       => 'Please select at least one leave type.',
      'whole_day_to.gte'     => 'End date must be on or after the start date.',
      'total_days.min'       => 'Leave duration must be at least half a day.',
    ]);

    // ── 2. Load the employee ───────────────────────────────────────────────

    $employee = Employee::findOrFail($validated['employee_id']);

    // ── 3. Resolve leave-type slugs → DB IDs ──────────────────────────────
    //
    // The form sends lowercase slugs; the leave_types table stores full names.
    // Using a static map avoids a full-table scan and keeps the logic explicit.

    $slugToName = [
      'vacation'  => 'Vacation Leave',
      'sick'      => 'Sick Leave',

      'sil'       => 'Service Incentive Leave',
    ];

    $unknownSlugs = collect($validated['leave_type'])
      ->filter(fn($slug) => ! array_key_exists($slug, $slugToName))
      ->values()
      ->all();

    if ($unknownSlugs) {
      return response()->json([
        'message' => 'Unrecognised leave type(s): ' . implode(', ', $unknownSlugs)
          . '. Please contact the administrator.',
        'errors'  => [
          'leave_type' => ['One or more selected leave types are not configured in the system.'],
        ],
      ], 422);
    }

    // Fetch all referenced leave types in one query — keyed by name
    $requestedNames = collect($validated['leave_type'])
      ->unique()
      ->map(fn($slug) => $slugToName[$slug])
      ->values()
      ->all();

    $leaveTypes = LeaveType::whereIn('name', $requestedNames)->get()->keyBy('name');

    $missingTypes = collect($requestedNames)
      ->filter(fn($name) => ! $leaveTypes->has($name))
      ->values()
      ->all();

    if ($missingTypes) {
      Log::warning('LeaveApplication store: leave types missing from DB', ['missing' => $missingTypes]);

      return response()->json([
        'message' => 'Some leave types are not set up in the system: '
          . implode(', ', $missingTypes)
          . '. Please contact the administrator.',
        'errors'  => [
          'leave_type' => ['One or more selected leave types are missing from the database.'],
        ],
      ], 422);
    }

    // Build slug → LeaveType model map
    $slugToModel = collect($validated['leave_type'])
      ->unique()
      ->mapWithKeys(fn($slug) => [$slug => $leaveTypes[$slugToName[$slug]]])
      ->all();

    // ── 4. Build the shared payload ────────────────────────────────────────

    $cause = $this->buildCause(
      $validated['leave_details'] ?? null,
      $validated['commutation']   ?? 'not_requested'
    );

    $sharedPayload = [
      'employee_id'    => $employee->id,
      'date_filed'     => $validated['date_of_filing'],
      'start_date'     => $validated['whole_day_from'] ?? null,
      'end_date'       => $validated['whole_day_to']   ?? null,
      'total_days'     => $validated['total_days'],
      'cause'          => $cause,
      'manager_status' => LeaveApplication::MANAGER_STATUSES[0], // 'pending'
      'reason'         => null,
      'remarks'        => LeaveApplication::REMARKS[0],           // 'pending'
    ];

    // ── 5. Insert inside a transaction ────────────────────────────────────
    //
    // One LeaveApplication row per leave type.
    // Leave balances are NOT deducted at filing time — only when HR approves.

    try {
      DB::transaction(function () use ($slugToModel, $sharedPayload) {
        foreach ($slugToModel as $leaveType) {
          LeaveApplication::create(
            array_merge($sharedPayload, ['leave_type_id' => $leaveType->id])
          );
        }
      });

      $count   = count($slugToModel);
      $message = $count === 1
        ? 'Leave application submitted successfully.'
        : "{$count} leave applications submitted successfully.";

      return response()->json(['message' => $message], 201);
    } catch (Throwable $e) {
      Log::error('LeaveApplication store failed', [
        'employee_id' => $validated['employee_id'],
        'error'       => $e->getMessage(),
        'trace'       => $e->getTraceAsString(),
      ]);

      return response()->json([
        'message' => 'Something went wrong while saving the application. Please try again.',
      ], 500);
    }
  }

    // ── Private helpers ───────────────────────────────────────────────────────

  /**
   * Compose the `cause` string written to the DB.
   *
   * Commutation status is appended as a bracketed suffix so it remains
   * visible in the summary view without requiring a dedicated column.
   *
   * Examples:
   *   "Medical check-up — [Commutation Requested]"
   *   "Vacation trip"
   *   "[Commutation Requested]"
   *   null  (no detail entered, commutation not requested)
   */
  private function buildCause(?string $details, string $commutation): ?string
  {
    $parts = array_filter([
      $details ? trim($details) : null,
      $commutation === 'requested' ? '[Commutation Requested]' : null,
    ]);

    return $parts ? implode(' — ', array_values($parts)) : null;
  }
}
