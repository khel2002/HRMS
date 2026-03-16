<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
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

  // ── POST /admin/leave-application ─────────────────────────────────────────
  //
  // One form submission may carry multiple leave_type[] values.
  // We insert one LeaveApplication row per leave type, all sharing the
  // same date range and employee — consistent with how the summary view
  // lists them.
  //
  // Form fields → DB mapping:
  //   employee_id      → employee_id
  //   leave_type[]     → leave_type_id  (resolved via leave_types.name lookup)
  //   date_of_filing   → date_filed
  //   whole_day_from   → start_date
  //   whole_day_to     → end_date
  //   total_days       → total_days
  //   leave_details    → cause
  //   commutation      → appended to cause (no dedicated column)
  //   manager_status   → default 'pending'
  //   remarks          → default 'pending'

  public function store(Request $request): JsonResponse
  {
    // ── 1. Validate ────────────────────────────────────────────────────────
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

    // ── 2. Resolve leave-type slugs → DB IDs ──────────────────────────────
    //
    // The form sends lowercase slugs (e.g. 'vacation', 'sil').
    // The leave_types table stores full names ('Vacation Leave', …).
    // The slug→name map must match whatever is seeded in leave_types.

    $slugToName = [
      'vacation'  => 'Vacation Leave',
      'sick'      => 'Sick Leave',
      'maternity' => 'Maternity Leave',
      'paternity' => 'Paternity Leave',
      'terminal'  => 'Terminal Leave',
      'sil'       => 'Service Incentive Leave',
    ];

    // Pre-load all referenced leave types in one query
    $requestedNames = collect($validated['leave_type'])
      ->map(fn($slug) => $slugToName[$slug] ?? null)
      ->filter()
      ->unique()
      ->values()
      ->all();

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

    $leaveTypes = LeaveType::whereIn('name', $requestedNames)->get()->keyBy('name');

    $missingTypes = collect($requestedNames)
      ->filter(fn($name) => ! $leaveTypes->has($name))
      ->values()
      ->all();

    if ($missingTypes) {
      Log::warning('LeaveApplication store: leave types not found in DB', ['missing' => $missingTypes]);

      return response()->json([
        'message' => 'Some leave types are not set up in the system: ' . implode(', ', $missingTypes)
          . '. Please contact the administrator.',
        'errors'  => [
          'leave_type' => ['One or more selected leave types are missing from the database.'],
        ],
      ], 422);
    }

    // Build slug → ID map for the insert loop
    $slugToId = collect($validated['leave_type'])
      ->unique()
      ->mapWithKeys(fn($slug) => [$slug => $leaveTypes[$slugToName[$slug]]->id])
      ->all();

    // ── 3. Build shared payload ────────────────────────────────────────────

    $cause = $this->buildCause(
      $validated['leave_details'] ?? null,
      $validated['commutation']   ?? 'not_requested'
    );

    $sharedPayload = [
      'employee_id'    => $validated['employee_id'],
      'date_filed'     => $validated['date_of_filing'],
      'start_date'     => $validated['whole_day_from'] ?? null,
      'end_date'       => $validated['whole_day_to']   ?? null,
      'total_days'     => $validated['total_days'],
      'cause'          => $cause,
      'department'     => null,
      'manager_status' => LeaveApplication::MANAGER_STATUSES[0], // 'pending'
      'reason'         => null,
      'remarks'        => LeaveApplication::REMARKS[0],           // 'pending'
    ];

    // ── 4. Insert one row per leave type inside a transaction ──────────────

    try {
      DB::transaction(function () use ($slugToId, $sharedPayload) {
        foreach ($slugToId as $leaveTypeId) {
          LeaveApplication::create(
            array_merge($sharedPayload, ['leave_type_id' => $leaveTypeId])
          );
        }
      });

      $count   = count($slugToId);
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
   * Build the `cause` string written to the DB.
   *
   * Commutation status is appended as a bracketed suffix so it is visible
   * in the leave summary view without requiring a dedicated column.
   *
   * Examples:
   *   "Medical check-up — [Commutation Requested]"
   *   "Vacation trip"
   *   "[Commutation Requested]"
   *   null  (nothing entered, not requested)
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
