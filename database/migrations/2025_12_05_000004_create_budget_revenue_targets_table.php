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
        Schema::create('budget_revenue_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->onDelete('cascade');
            $table->integer('month'); // 1-12
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            // Unique constraint: satu target per bulan per periode
            $table->unique(['budget_period_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_revenue_targets');
    }
};
