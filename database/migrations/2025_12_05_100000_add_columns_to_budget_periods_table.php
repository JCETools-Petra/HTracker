<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_periods', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('budget_periods', 'total_revenue_target')) {
                $table->decimal('total_revenue_target', 15, 2)->default(0)->after('year');
            }

            if (!Schema::hasColumn('budget_periods', 'total_expense_budget')) {
                $table->decimal('total_expense_budget', 15, 2)->default(0)->after('total_revenue_target');
            }

            if (!Schema::hasColumn('budget_periods', 'target_profit')) {
                $table->decimal('target_profit', 15, 2)->default(0)->after('total_expense_budget');
            }

            if (!Schema::hasColumn('budget_periods', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }

            if (!Schema::hasColumn('budget_periods', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('budget_periods', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('budget_periods', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->onDelete('set null');
            }

            // Modify status column to add 'submitted' option
            $table->enum('status', ['draft', 'submitted', 'approved', 'locked'])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_periods', function (Blueprint $table) {
            $table->dropColumn([
                'total_revenue_target',
                'total_expense_budget',
                'target_profit',
                'notes',
                'submitted_at',
                'approved_at',
                'approved_by'
            ]);

            // Revert status enum to original
            $table->enum('status', ['draft', 'approved', 'locked'])->default('draft')->change();
        });
    }
};
