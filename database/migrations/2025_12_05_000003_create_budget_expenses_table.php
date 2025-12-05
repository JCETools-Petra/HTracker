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
        Schema::create('budget_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_department_id')->constrained('budget_departments')->onDelete('cascade');
            $table->date('expense_date');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('category')->nullable(); // Supplies, Payroll, Utilities, etc
            $table->string('receipt_number')->nullable();
            $table->string('receipt_file')->nullable(); // Path to uploaded file
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Index untuk performa query
            $table->index(['budget_department_id', 'expense_date']);
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_expenses');
    }
};
