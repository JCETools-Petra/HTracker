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
        Schema::create('budget_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->onDelete('cascade');
            $table->integer('month'); // 1-12
            $table->decimal('target_occupancy_pct', 5, 2)->default(0); // Contoh: 75.50 (%)
            $table->decimal('target_adr', 12, 2)->default(0); // Average Daily Rate
            $table->integer('days_in_month')->default(30);
            $table->timestamps();

            // Unique constraint: satu driver per bulan per periode
            $table->unique(['budget_period_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_drivers');
    }
};
