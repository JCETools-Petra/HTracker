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
        Schema::create('budget_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->integer('year');
            $table->decimal('total_revenue_target', 15, 2)->default(0);
            $table->decimal('total_expense_budget', 15, 2)->default(0);
            $table->decimal('target_profit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'locked'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint: satu property hanya bisa punya satu budget per tahun
            $table->unique(['property_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_periods');
    }
};
