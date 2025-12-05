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
        Schema::create('budget_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->onDelete('cascade');
            $table->string('name'); // Rooms, F&B, Marketing, Maintenance, Admin
            $table->string('code', 50)->nullable(); // RMS, FNB, MKT, MNT, ADM
            $table->decimal('allocated_budget', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Index untuk performa
            $table->index(['budget_period_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_departments');
    }
};
