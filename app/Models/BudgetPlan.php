<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_period_id',
        'budget_category_id',
        'month',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relasi ke Budget Period
     */
    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    /**
     * Relasi ke Budget Category
     */
    public function budgetCategory(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $periodId)
    {
        return $query->where('budget_period_id', $periodId);
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('budget_category_id', $categoryId);
    }

    /**
     * Get nama bulan dari angka bulan
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return $months[$this->month] ?? '';
    }
}
