<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetRevenueTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_period_id',
        'month',
        'target_amount',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];

    /**
     * Relasi ke Budget Period
     */
    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    /**
     * Get actual revenue untuk bulan ini
     */
    public function getActualRevenueAttribute(): float
    {
        $budgetPeriod = $this->budgetPeriod;

        return DailyIncome::where('property_id', $budgetPeriod->property_id)
            ->whereYear('date', $budgetPeriod->year)
            ->whereMonth('date', $this->month)
            ->sum('total_revenue');
    }

    /**
     * Get variance (actual - target)
     */
    public function getVarianceAttribute(): float
    {
        return $this->actual_revenue - $this->target_amount;
    }

    /**
     * Get variance percentage
     */
    public function getVariancePercentageAttribute(): float
    {
        if ($this->target_amount == 0) return 0;
        return ($this->variance / $this->target_amount) * 100;
    }

    /**
     * Check if target achieved
     */
    public function isTargetAchieved(): bool
    {
        return $this->actual_revenue >= $this->target_amount;
    }

    /**
     * Get nama bulan
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

    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }
}
