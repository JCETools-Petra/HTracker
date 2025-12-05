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
        Schema::create('budget_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->onDelete('cascade');
            $table->foreignId('budget_category_id')->constrained('budget_categories')->onDelete('cascade');
            $table->integer('month'); // 1-12
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: satu kategori per bulan per periode
            $table->unique(['budget_period_id', 'budget_category_id', 'month'], 'budget_plans_unique');

            // Index untuk performa query
            $table->index(['budget_period_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_plans');
    }
};
