<?php

namespace App\Console\Commands;

use App\Models\LeaveBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rolls over leave balances from one year into the next.
 *
 * Only leave types with a carry-over policy are processed
 * (currently: Sick Leave — unused days carry forward, max 14).
 *
 * Leave types with no carry-over (Vacation Leave, SIL) are
 * intentionally skipped here; their fresh annual rows are
 * created lazily by LeaveBalance::resolveForYear() on first access.
 *
 * Usage:
 *   php artisan leave:rollover              # rolls over (current year - 1) → current year
 *   php artisan leave:rollover --from=2024  # explicit source year
 *   php artisan leave:rollover --from=2024 --to=2025
 *
 * Schedule (in App\Console\Kernel):
 *   $schedule->command('leave:rollover')->yearlyOn(1, 1, '00:05');
 */
class RolloverLeaveBalances extends Command
{
  protected $signature = 'leave:rollover
                            {--from= : Source year (defaults to current year - 1)}
                            {--to=   : Target year (defaults to current year)}';

  protected $description = 'Roll over unused leave balances (e.g. Sick Leave carry-over) into the new year.';

  public function handle(): int
  {
    $fromYear = (int) ($this->option('from') ?: now()->year - 1);
    $toYear   = (int) ($this->option('to')   ?: now()->year);

    if ($toYear <= $fromYear) {
      $this->error("Target year ({$toYear}) must be greater than source year ({$fromYear}).");
      return self::FAILURE;
    }

    $this->info("Rolling over leave balances: {$fromYear} → {$toYear}");

    try {
      $result = LeaveBalance::rolloverYear($fromYear, $toYear);

      $this->info("Done. Processed: {$result['processed']} | Skipped (already exists): {$result['skipped']}");

      Log::info('leave:rollover completed', [
        'from'      => $fromYear,
        'to'        => $toYear,
        'processed' => $result['processed'],
        'skipped'   => $result['skipped'],
      ]);

      return self::SUCCESS;
    } catch (Throwable $e) {
      $this->error('Rollover failed: ' . $e->getMessage());

      Log::error('leave:rollover failed', [
        'from'  => $fromYear,
        'to'    => $toYear,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return self::FAILURE;
    }
  }
}
