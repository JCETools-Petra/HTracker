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
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('cascade');
            $table->string('code', 50)->unique(); // Kode akun (e.g., '4000', '5100')
            $table->string('name'); // Nama akun (e.g., 'Room Revenue', 'Room Expense')
            $table->enum('type', ['revenue', 'expense_fixed', 'expense_variable', 'payroll'])->default('revenue');
            $table->string('department')->nullable(); // Departemen (e.g., 'Rooms', 'F&B', 'Admin')
            $table->foreignId('parent_id')->nullable()->constrained('budget_categories')->onDelete('cascade');
            $table->integer('sort_order')->default(0); // Urutan tampilan
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index untuk performa query
            $table->index(['property_id', 'type']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
    }
};
