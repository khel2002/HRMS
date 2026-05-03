<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix employee_recurring_payroll_items for the new flat schema.
 *
 * OLD schema: payroll_item_type_id (NOT NULL FK) + category/group driven by PayrollItemType
 * NEW schema: type (Earning|Deduction) + category_code (e.g. housing_loan) stored directly
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('employee_recurring_payroll_items', function (Blueprint $table) {

      // ── 1. Drop the old FK constraint first ───────────────────────────
      // The FK name varies by how the original migration was written.
      // We try the two most common naming patterns.
      $this->dropForeignIfExists('employee_recurring_payroll_items', 'payroll_item_type_id');

      // ── 2. Drop the old column ────────────────────────────────────────
      if (Schema::hasColumn('employee_recurring_payroll_items', 'payroll_item_type_id')) {
        $table->dropColumn('payroll_item_type_id');
      }

      // ── 3. Add new flat columns (if not already present) ──────────────
      if (! Schema::hasColumn('employee_recurring_payroll_items', 'type')) {
        $table->enum('type', ['Earning', 'Deduction'])
          ->default('Deduction')
          ->after('employee_id');
      }

      if (! Schema::hasColumn('employee_recurring_payroll_items', 'category_code')) {
        $table->string('category_code', 100)
          ->default('')
          ->after('type');
      }

      // ── 4. Ensure application_timing exists ───────────────────────────
      if (! Schema::hasColumn('employee_recurring_payroll_items', 'application_timing')) {
        $table->enum('application_timing', [
          'every_cutoff',
          'first_cutoff_only',
          'second_cutoff_only',
        ])->default('every_cutoff')->after('category_code');
      }

      // ── 5. Ensure start_date / end_date exist ─────────────────────────
      if (! Schema::hasColumn('employee_recurring_payroll_items', 'start_date')) {
        $table->date('start_date')->nullable()->after('application_timing');
      }

      if (! Schema::hasColumn('employee_recurring_payroll_items', 'end_date')) {
        $table->date('end_date')->nullable()->after('start_date');
      }

      // ── 6. Ensure notes column exists ─────────────────────────────────
      if (! Schema::hasColumn('employee_recurring_payroll_items', 'notes')) {
        $table->text('notes')->nullable()->after('end_date');
      }
    });

    // ── 7. Add a performance index ────────────────────────────────────────
    $this->addIndexIfMissing(
      'employee_recurring_payroll_items',
      ['employee_id', 'is_active'],
      'erpi_employee_active_idx'
    );
  }

  public function down(): void
  {
    Schema::table('employee_recurring_payroll_items', function (Blueprint $table) {
      // Re-add the old column (nullable so rollback doesn't break existing rows)
      if (! Schema::hasColumn('employee_recurring_payroll_items', 'payroll_item_type_id')) {
        $table->unsignedBigInteger('payroll_item_type_id')->nullable()->after('employee_id');
      }

      // Drop the new columns
      $columnsToDrop = array_filter(
        ['type', 'category_code'],
        fn($col) => Schema::hasColumn('employee_recurring_payroll_items', $col)
      );

      if (! empty($columnsToDrop)) {
        $table->dropColumn($columnsToDrop);
      }
    });

    // Drop index
    try {
      Schema::table('employee_recurring_payroll_items', function (Blueprint $table) {
        $table->dropIndex('erpi_employee_active_idx');
      });
    } catch (\Throwable) {
      // Index may not exist
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Helpers
  // ─────────────────────────────────────────────────────────────────────────

  private function dropForeignIfExists(string $table, string $column): void
  {
    // Try both Laravel naming conventions
    $candidates = [
      "{$table}_{$column}_foreign",                   // explicit
      str_replace('employee_recurring_payroll_items', 'erpi', "{$table}_{$column}_foreign"), // short form
    ];

    // Also introspect the actual FK names from the DB
    $actualForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

    foreach ($actualForeignKeys as $fk) {
      $candidates[] = $fk->CONSTRAINT_NAME;
    }

    foreach (array_unique($candidates) as $fkName) {
      try {
        Schema::table($table, function (Blueprint $t) use ($fkName) {
          $t->dropForeign($fkName);
        });
        break; // Success — stop trying
      } catch (\Throwable) {
        // This name didn't work, try next
      }
    }
  }

  private function addIndexIfMissing(string $table, array $columns, string $indexName): void
  {
    $exists = DB::select(
      "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
      [$indexName]
    );

    if (empty($exists)) {
      Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
        $t->index($columns, $indexName);
      });
    }
  }
};
