<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_department_id',
        'expense_date',
        'description',
        'amount',
        'category',
        'receipt_number',
        'receipt_file',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    /**
     * Relasi ke Budget Department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(BudgetDepartment::class, 'budget_department_id');
    }

    /**
     * Relasi ke User yang membuat
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeInMonth($query, $year, $month)
    {
        return $query->whereYear('expense_date', $year)
                    ->whereMonth('expense_date', $month);
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
