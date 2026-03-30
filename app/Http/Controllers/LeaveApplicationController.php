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
  // Returns the current-year leave balances for an employee.
  //
  // When no balance row exists yet for the current year, resolveForYear()
  // is called — which applies carry-over rules before creating the row.
  // This means a Sick Leave employee who had 3 days remaining last year
  // will correctly see 8 days (3 carried + 5 new) instead of a flat 5.

  public function balances(int $employeeId): JsonResponse
  {
    $employee = Employee::with(['office', 'position'])
      ->findOrFail($employeeId);

    $year = now()->year;

    $trackedSlugs = [
      'vacation' => 'Vacation Leave',
      'sick'     => 'Sick Leave',
      'sil'      => 'Service Incentive Leave',
    ];

    $policies = [
      'vacation' => '5 days/year — resets annually',
      'sick'     => 'Unused credits carry over (max 14 days)',
      'sil'      => 'Must be used within the year — resets to 0',
    ];

    $leaveTypes = LeaveType::whereIn('name', array_values($trackedSlugs))
      ->get()
      ->keyBy('name');

    $balances = [];

    foreach ($trackedSlugs as $slug => $dbName) {
      $leaveType = $leaveTypes->get($dbName);
      if (! $leaveType) {
        continue;
      }

      // resolveForYear() returns the existing row if present, or creates
      // a new one with carry-over logic applied (sick leave) or a fresh
      // annual default (vacation, SIL).
      $row = LeaveBalance::resolveForYear($employeeId, $leaveType->id, $year);

      $balances[] = [
        'slug'           => $slug,
        'name'           => $dbName,
        'total_days'     => (float) $row->total_days,
        'used_days'      => (float) $row->used_days,
        'remaining_days' => (float) $row->remaining_days,
        'policy'         => $policies[$slug],
      ];
    }

    return response()->json([
      'employee_id'   => $employee->id,
      'office_name'   => $employee->office?->office_name   ?? '—',
      'position_name' => $employee->position?->position_name ?? '—',
      'year'          => $year,
      'balances'      => $balances,
    ]);
  }

  // ── POST /admin/leave-application ─────────────────────────────────────────
  //
  // Accepts:
  //   whole_day_from / whole_day_to  — optional full-day date range
  //   half_day[N][date]              — individual half-day dates
  //   half_day[N][session]           — AM or PM
  //
  // total_days is computed SERVER-SIDE from the submitted dates.
  // The hidden `total_days` field from JS is used only as a fallback
  // if no date inputs are provided (should not happen in practice).
  //
  // duration_type is derived:
  //   Only half-days submitted, single entry  → half_day_am / half_day_pm
  //   Only half-days submitted, multiple      → full_day (mixed, treat as full)
  //   Mix of whole + half days                → full_day
  //   Only whole days                         → full_day

  public function store(Request $request): JsonResponse
  {
    // ── 1. Validate ───────────────────────────────────────────────────────

    $validated = $request->validate([
      'employee_id'        => ['required', 'integer', 'exists:employees,id'],
      'leave_type'         => ['required', 'array', 'min:1'],
      'leave_type.*'       => ['required', 'string'],
      'date_of_filing'     => ['required', 'date'],
      'whole_day_from'     => ['nullable', 'date'],
      'whole_day_to'       => ['nullable', 'date', 'gte:whole_day_from'],
      'half_day'           => ['nullable', 'array'],
      'half_day.*.date'    => ['required', 'date'],
      'half_day.*.session' => ['required', 'in:AM,PM'],
      'leave_details'      => ['nullable', 'string', 'max:500'],
      'commutation'        => ['nullable', 'in:requested,not_requested'],
      'salary'             => ['nullable', 'string', 'max:50'],
    ], [
      'employee_id.required' => 'Please search and select an employee.',
      'employee_id.exists'   => 'The selected employee no longer exists.',
      'leave_type.required'  => 'Please select at least one leave type.',
      'leave_type.min'       => 'Please select at least one leave type.',
      'whole_day_to.gte'     => 'End date must be on or after the start date.',
    ]);

    // ── 2. Load employee ──────────────────────────────────────────────────

    $employee = Employee::findOrFail($validated['employee_id']);

    // ── 3. Resolve leave-type slugs → models ──────────────────────────────

    $slugToName = [
      'vacation' => 'Vacation Leave',
      'sick'     => 'Sick Leave',
      'sil'      => 'Service Incentive Leave',
    ];

    $unknownSlugs = collect($validated['leave_type'])
      ->filter(fn($s) => ! array_key_exists($s, $slugToName))
      ->values()->all();

    if ($unknownSlugs) {
      return response()->json([
        'message' => 'Unrecognised leave type(s): ' . implode(', ', $unknownSlugs),
        'errors'  => ['leave_type' => ['One or more leave types are not configured.']],
      ], 422);
    }

    $requestedNames = collect($validated['leave_type'])
      ->unique()->map(fn($s) => $slugToName[$s])->values()->all();

    $leaveTypes = LeaveType::whereIn('name', $requestedNames)->get()->keyBy('name');

    $missingTypes = collect($requestedNames)
      ->filter(fn($n) => ! $leaveTypes->has($n))->values()->all();

    if ($missingTypes) {
      return response()->json([
        'message' => 'Leave types not found in DB: ' . implode(', ', $missingTypes),
        'errors'  => ['leave_type' => ['One or more leave types are missing from the database.']],
      ], 422);
    }

    $slugToModel = collect($validated['leave_type'])
      ->unique()
      ->mapWithKeys(fn($s) => [$s => $leaveTypes[$slugToName[$s]]])
      ->all();

    // ── 4. Compute total_days and duration_type server-side ───────────────

    $wholeDayFrom = $validated['whole_day_from'] ?? null;
    $wholeDayTo   = $validated['whole_day_to']   ?? null;
    $halfDays     = $validated['half_day']        ?? [];

    // Count whole working days (Monday–Saturday, excluding Sunday)
    $wholeDayCount = 0;
    if ($wholeDayFrom && $wholeDayTo) {
      $cur = new \DateTime($wholeDayFrom);
      $end = new \DateTime($wholeDayTo);
      while ($cur <= $end) {
        if ((int) $cur->format('N') !== 7) { // 7 = Sunday
          $wholeDayCount++;
        }
        $cur->modify('+1 day');
      }
    }

    // Each half-day entry = 0.5 days
    $halfDayCount = count($halfDays) * 0.5;

    $totalDays = $wholeDayCount + $halfDayCount;

    // Fallback to JS-submitted value if both date inputs were empty
    // (edge case: should not normally happen)
    if ($totalDays <= 0) {
      $totalDays = (float) ($request->input('total_days', 0));
    }

    if ($totalDays < 0.5) {
      return response()->json([
        'message' => 'Please enter at least one leave date.',
        'errors'  => ['whole_day_from' => ['Leave duration must be at least half a day.']],
      ], 422);
    }

    // Derive duration_type
    $durationType = 'full_day';
    if ($wholeDayCount === 0 && count($halfDays) === 1) {
      $durationType = strtolower($halfDays[0]['session']) === 'am'
        ? 'half_day_am'
        : 'half_day_pm';
    }

    // Derive start_date / end_date
    $startDate = $wholeDayFrom;
    $endDate   = $wholeDayTo;

    if (! $startDate && count($halfDays) > 0) {
      $halfDates = collect($halfDays)->pluck('date')->sort()->values();
      $startDate = $halfDates->first();
      $endDate   = $halfDates->last();
    }

    // ── 5. Build cause string ─────────────────────────────────────────────

    $cause = $this->buildCause(
      $validated['leave_details'] ?? null,
      $validated['commutation']   ?? 'not_requested',
      $halfDays
    );

    // ── 6. Build shared payload ───────────────────────────────────────────

    $sharedPayload = [
      'employee_id'    => $employee->id,
      'date_filed'     => $validated['date_of_filing'],
      'start_date'     => $startDate,
      'end_date'       => $endDate,
      'total_days'     => $totalDays,
      'duration_type'  => $durationType,
      'cause'          => $cause,
      'manager_status' => LeaveApplication::MANAGER_STATUSES[0], // 'pending'
      'reason'         => null,
      'remarks'        => LeaveApplication::REMARKS[0],           // 'pending'
    ];

    // ── 7. Insert (one row per leave type) ────────────────────────────────
    //
    // Balance deduction happens only when HR approves, not at filing time.

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
   * Build the cause string stored in the DB.
   * Appends commutation status and half-day schedule as readable suffixes.
   */
  private function buildCause(?string $details, string $commutation, array $halfDays = []): ?string
  {
    $parts = array_filter([
      $details ? trim($details) : null,
      $commutation === 'requested' ? '[Commutation Requested]' : null,
    ]);

    if (count($halfDays) > 0) {
      $schedule = collect($halfDays)
        ->map(fn($hd) => $hd['date'] . ' (' . $hd['session'] . ')')
        ->implode(', ');
      $parts[] = '[Half-day(s): ' . $schedule . ']';
    }

    return $parts ? implode(' — ', array_values($parts)) : null;
  }
}
