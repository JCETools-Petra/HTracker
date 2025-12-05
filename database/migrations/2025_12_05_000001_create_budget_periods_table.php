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
            $table->enum('status', ['draft', 'approved', 'locked'])->default('draft');
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
