<?php

namespace App\Services;

use App\Models\EmployeeRecurringPayrollItem;
use App\Models\EmployeeSalarySetting;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollItemType;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayslipService
{
  /** Payroll statuses that must never be overwritten by re-processing. */
  private const LOCKED_STATUSES = ['approved', 'released'];

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Process (create/update) both cutoffs for a given employee + payroll month.
   *
   * Cutoff schedule (Philippine semi-monthly):
   *   1st cutoff → 26th of the previous month  →  10th of the current month
   *   2nd cutoff → 11th of the current month   →  25th of the current month
   *
   * Approved/released payrolls are never overwritten.
   *
   * @return array{first: Payroll, second: Payroll}
   */
  public function processMonth(int $employeeId, int $year, int $month, int $preparedByUserId): array
  {
    return DB::transaction(function () use ($employeeId, $year, $month, $preparedByUserId) {
      $firstPeriod  = $this->upsertPeriod($year, $month, 'first_cutoff',  $preparedByUserId);
      $secondPeriod = $this->upsertPeriod($year, $month, 'second_cutoff', $preparedByUserId);

      $firstPayroll  = $this->processCutoff($employeeId, $firstPeriod,  $year, $month);
      $secondPayroll = $this->processCutoff($employeeId, $secondPeriod, $year, $month);

      return ['first' => $firstPayroll, 'second' => $secondPayroll];
    });
  }

  /**
   * Return the [dateFrom, dateTo, humanName] for a cutoff.
   * Public so controllers/views can display the correct label without re-deriving it.
   *
   * @return array{0: Carbon, 1: Carbon, 2: string}
   */
  public function cutoffDateRange(int $year, int $month, string $cutoffType): array
  {
    $anchor = Carbon::create($year, $month, 1)->startOfDay();

    if ($cutoffType === 'first_cutoff') {
      $dateFrom = $anchor->copy()->subMonthNoOverflow()->setDay(26)->startOfDay();
      $dateTo   = $anchor->copy()->setDay(10)->startOfDay();
      $name     = $anchor->format('M Y') . ' — 1st Cutoff (26th–10th)';
    } else {
      $dateFrom = $anchor->copy()->setDay(11)->startOfDay();
      $dateTo   = $anchor->copy()->setDay(25)->startOfDay();
      $name     = $anchor->format('M Y') . ' — 2nd Cutoff (11th–25th)';
    }

    return [$dateFrom, $dateTo, $name];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Period upsert
  // ─────────────────────────────────────────────────────────────────────────

  private function upsertPeriod(
    int    $year,
    int    $month,
    string $cutoffType,
    int    $preparedByUserId,
  ): PayrollPeriod {
    [$dateFrom, $dateTo, $name] = $this->cutoffDateRange($year, $month, $cutoffType);

    return PayrollPeriod::query()->updateOrCreate(
      ['year' => $year, 'month' => $month, 'cutoff_type' => $cutoffType],
      [
        'name'                => $name,
        'date_from'           => $dateFrom->toDateString(),
        'date_to'             => $dateTo->toDateString(),
        'status'              => 'computed',
        'prepared_by_user_id' => $preparedByUserId,
        'prepared_at'         => now(),
      ],
    );
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Cutoff computation
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Compute (or skip if locked) one cutoff's payroll record.
   *
   * WHY $year/$month are passed separately:
   *   The salary anchor is always the 1st of the payroll month — not date_to.
   *   This prevents "no salary found" when the 1st cutoff's date_to (10th)
   *   is earlier than the effective_date set for that month.
   */
  private function processCutoff(
    int           $employeeId,
    PayrollPeriod $period,
    int           $year,
    int           $month,
  ): Payroll {
    // Guard: never overwrite a locked payroll.
    $existing = Payroll::query()
      ->where('payroll_period_id', $period->id)
      ->where('employee_id', $employeeId)
      ->first();

    if ($existing && in_array($existing->status, self::LOCKED_STATUSES, true)) {
      return $existing->load(['payrollPeriod', 'items.payrollItemType']);
    }

    // Resolve salary — anchor on 1st of the payroll month.
    $salaryAnchor  = Carbon::create($year, $month, 1)->startOfDay();
    $salarySetting = $this->resolveSalarySetting($employeeId, $salaryAnchor);

    // Prefer explicit semi_monthly_rate; derive from monthly_rate as fallback.
    $basicPay = (float) ($salarySetting->semi_monthly_rate ?? 0);
    if ($basicPay <= 0) {
      $basicPay = ((float) ($salarySetting->monthly_rate ?? 0)) / 2;
    }

    if ($basicPay <= 0) {
      throw new RuntimeException(
        'Employee has no valid salary rate configured. '
          . 'Please set the monthly or semi-monthly rate in Salary Settings.'
      );
    }

    // Build items → totals → payroll row → items insert (atomic).
    $items = $this->buildItems($employeeId, $period, $basicPay);

    [$totalEarnings, $totalDeductions] = $this->sumItems($items);
    $grossPay = $totalEarnings;
    $netPay   = $grossPay - $totalDeductions;

    $payroll = Payroll::query()->updateOrCreate(
      ['payroll_period_id' => $period->id, 'employee_id' => $employeeId],
      [
        'employee_salary_setting_id' => $salarySetting->id,
        'basic_salary'               => $basicPay,
        'total_earnings'             => $totalEarnings,
        'total_deductions'           => $totalDeductions,
        'gross_pay'                  => $grossPay,
        'net_pay'                    => $netPay,
        'status'                     => 'computed',
        'remarks'                    => null,
      ],
    );

    // Atomically replace all line items.
    PayrollItem::query()->where('payroll_id', $payroll->id)->delete();

    $now  = now();
    $rows = array_map(
      static fn(array $item): array => array_merge($item, [
        'payroll_id' => $payroll->id,
        'created_at' => $now,
        'updated_at' => $now,
      ]),
      $items,
    );

    if (! empty($rows)) {
      PayrollItem::query()->insert($rows);
    }

    return $payroll->load(['payrollPeriod', 'items.payrollItemType']);
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Item building
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Build the flat, sorted array of payroll-item rows for a single cutoff.
   *
   * Earning vs Deduction is determined solely by the recurring item's `type`
   * column ('Earning' | 'Deduction') — NOT by the category_code.
   * Cutoff inclusion is determined solely by application_timing.
   *
   * @return array<int, array<string, mixed>>
   */
  private function buildItems(int $employeeId, PayrollPeriod $period, float $basicPay): array
  {
    $cutoffType = $period->cutoff_type;

    $basicType = $this->ensureItemType(
      code: 'earning_BASIC_SALARY',
      name: 'Basic Salary',
      category: 'earning',
    );

    // Basic pay is always present in both cutoffs.
    $items = [
      [
        'payroll_item_type_id' => $basicType->id,
        'category'             => 'earning',
        'group_type'           => null,
        'label'                => $cutoffType === 'first_cutoff'
          ? 'Basic Pay (1st Cutoff)'
          : 'Basic Pay (2nd Cutoff)',
        'amount'               => $basicPay,
        'quantity'             => 1,
        'rate'                 => $basicPay,
        'application_timing'   => 'every_cutoff',
        'is_system_generated'  => true,
        'sort_order'           => 10,
        'notes'                => null,
      ],
    ];

    // Load and filter recurring items by timing.
    $recurring = $this->loadRecurringItems($employeeId, $period);
    $included  = $this->filterItemsForCutoff($recurring, $cutoffType);

    foreach ($included as $row) {
      // Earning/Deduction comes from the `type` column — source of truth.
      $category = strtolower((string) ($row->type ?? '')) === 'deduction'
        ? 'deduction'
        : 'earning';

      $label    = $this->humanizeCode((string) ($row->category_code ?? ''));

      // Embed category in code to prevent cross-category collision when two
      // employees have the same category_code but different types.
      $typeCode = $category . '_RECURRING_' . strtoupper((string) ($row->category_code ?? 'UNKNOWN'));

      $itemType = $this->ensureItemType(
        code: $typeCode,
        name: $label,
        category: $category,
      );

      $items[] = [
        'payroll_item_type_id' => $itemType->id,
        'category'             => $category,
        'group_type'           => null,
        'label'                => $label,
        'amount'               => (float) $row->amount,
        'quantity'             => 1,
        'rate'                 => (float) $row->amount,
        'application_timing'   => $row->application_timing ?? 'every_cutoff',
        'is_system_generated'  => false,
        'sort_order'           => 50,
        'notes'                => $row->notes,
      ];
    }

    // Sort: earnings first → deductions; within each group ascending sort_order.
    usort($items, static function (array $a, array $b): int {
      $rank = static fn(string $cat): int => $cat === 'earning' ? 0 : 1;
      $diff = $rank($a['category']) <=> $rank($b['category']);

      return $diff !== 0 ? $diff : ($a['sort_order'] ?? 50) <=> ($b['sort_order'] ?? 50);
    });

    return $items;
  }

  /** @return array{0: float, 1: float} [totalEarnings, totalDeductions] */
  private function sumItems(array $items): array
  {
    $earnings   = 0.0;
    $deductions = 0.0;

    foreach ($items as $item) {
      if (($item['category'] ?? '') === 'deduction') {
        $deductions += (float) ($item['amount'] ?? 0);
      } else {
        $earnings += (float) ($item['amount'] ?? 0);
      }
    }

    return [$earnings, $deductions];
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Salary resolution
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Find the most-recently effective active salary setting on or before $asOf.
   *
   * Two-step strategy:
   *   1. Active setting with effective_date ≤ $asOf  (normal case)
   *   2. Earliest active setting regardless of date   (handles future-dated effective_date)
   *
   * @throws RuntimeException when no active salary setting exists at all.
   */
  private function resolveSalarySetting(int $employeeId, Carbon $asOf): EmployeeSalarySetting
  {
    // Primary: active, on-or-before match.
    $salary = EmployeeSalarySetting::query()
      ->where('employee_id', $employeeId)
      ->where('is_active', true)
      ->whereDate('effective_date', '<=', $asOf->toDateString())
      ->orderByDesc('effective_date')
      ->orderByDesc('id')
      ->first();

    if ($salary) {
      return $salary;
    }

    // Fallback: any active setting (e.g. admin saved with a future effective_date).
    $salary = EmployeeSalarySetting::query()
      ->where('employee_id', $employeeId)
      ->where('is_active', true)
      ->orderBy('effective_date')
      ->orderBy('id')
      ->first();

    if ($salary) {
      return $salary;
    }

    throw new RuntimeException(
      'No active salary setting found for this employee. '
        . 'Please configure their salary in Salary Settings before processing payroll.'
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Recurring items
  // ─────────────────────────────────────────────────────────────────────────

  private function loadRecurringItems(int $employeeId, PayrollPeriod $period): EloquentCollection
  {
    $from = Carbon::parse($period->date_from)->startOfDay();
    $to   = Carbon::parse($period->date_to)->endOfDay();

    return EmployeeRecurringPayrollItem::query()
      ->where('employee_id', $employeeId)
      ->where('is_active', true)
      ->where(function ($q) use ($to): void {
        $q->whereNull('start_date')
          ->orWhereDate('start_date', '<=', $to->toDateString());
      })
      ->where(function ($q) use ($from): void {
        $q->whereNull('end_date')
          ->orWhereDate('end_date', '>=', $from->toDateString());
      })
      ->orderBy('id')
      ->get();
  }

  /**
   * Filter recurring items to those whose application_timing allows this cutoff.
   * Earning vs Deduction and category_code carry no cutoff-inclusion logic.
   */
  private function filterItemsForCutoff(EloquentCollection $items, string $cutoffType): EloquentCollection
  {
    return $items->filter(
      fn(EmployeeRecurringPayrollItem $row): bool =>
      $this->timingApplies($row->application_timing, $cutoffType)
    )->values();
  }

  private function timingApplies(?string $timing, string $cutoffType): bool
  {
    return match ($timing ?: 'every_cutoff') {
      'every_cutoff'       => true,
      'first_cutoff_only'  => $cutoffType === 'first_cutoff',
      'second_cutoff_only' => $cutoffType === 'second_cutoff',
      default              => true,
    };
  }

    // ─────────────────────────────────────────────────────────────────────────
    // PayrollItemType helpers
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Ensure a PayrollItemType row exists for the given code.
   *
   * The code is the sole lookup key. Category is embedded in the code
   * (e.g. 'deduction_RECURRING_HOUSING_LOAN') so a Deduction and an Earning
   * with the same category_code never share a type row.
   */
  private function ensureItemType(string $code, string $name, string $category): PayrollItemType
  {
    return PayrollItemType::query()->firstOrCreate(
      ['code' => $code],
      [
        'name'               => $name,
        'category'           => $category,
        'group_type'         => null,
        'application_timing' => 'every_cutoff',
        'calculation_type'   => 'manual',
        'is_taxable'         => false,
        'is_mandatory'       => false,
        'is_active'          => true,
        'sort_order'         => 0,
        'description'        => null,
      ],
    );
  }

  /**
   * Convert a snake_case category_code to a human-readable label.
   *
   * Examples:
   *   housing_loan       → Housing Loan
   *   sss_salary_loan    → SSS Salary Loan
   *   hdmf_calamity_loan → HDMF Calamity Loan
   *   pagibig_loan       → PAG-IBIG Loan
   *   fb_real_estate_loan → FB Real Estate Loan
   */
  public static function humanizeCategoryCode(?string $code): string
  {
    if (empty($code)) {
      return 'N/A';
    }

    $labels = [
      'sss_salary_loan'     => 'SSS Salary Loan',
      'sss_calamity_loan'   => 'SSS Calamity Loan',
      'hdmf_loan'           => 'HDMF Loan',
      'pagibig_loan'        => 'Pag-IBIG Loan',
      'philhealth'          => 'PhilHealth',
      'sss'                 => 'SSS',
      'pagibig'             => 'Pag-IBIG',
      'withholding_tax'     => 'Withholding Tax',
      'cash_advance'        => 'Cash Advance',
      'housing_loan'        => 'Housing Loan',
      'salary_adjustment'   => 'Salary Adjustment',
      'allowance'           => 'Allowance',
      'bonus'               => 'Bonus',
      'incentive'           => 'Incentive',
      'overtime'            => 'Overtime',
    ];

    if (array_key_exists($code, $labels)) {
      return $labels[$code];
    }

    return collect(explode('_', $code))
      ->filter()
      ->map(fn($word) => strtoupper($word) === $word ? $word : ucfirst($word))
      ->implode(' ');
  }
  public static function humanizeCode(?string $code): string
  {
    return self::humanizeCategoryCode($code);
  }
}
