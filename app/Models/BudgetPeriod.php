<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'year',
        'total_revenue_target',
        'total_expense_budget',
        'target_profit',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'total_revenue_target' => 'decimal:2',
        'total_expense_budget' => 'decimal:2',
        'target_profit' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Relasi ke Property
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relasi ke Budget Departments
     */
    public function departments(): HasMany
    {
        return $this->hasMany(BudgetDepartment::class);
    }

    /**
     * Relasi ke Revenue Targets
     */
    public function revenueTargets(): HasMany
    {
        return $this->hasMany(BudgetRevenueTarget::class);
    }

    /**
     * Relasi ke User yang approve
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all expenses through departments
     */
    public function expenses()
    {
        return $this->hasManyThrough(
            BudgetExpense::class,
            BudgetDepartment::class,
            'budget_period_id',
            'budget_department_id'
        );
    }

    /**
     * Check apakah budget masih draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check apakah budget sudah submitted
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check apakah budget sudah approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check apakah budget sudah locked
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Hitung total expense yang sudah dipakai
     */
    public function getTotalExpenseUsedAttribute(): float
    {
        return $this->departments->sum(function ($dept) {
            return $dept->expenses->sum('amount');
        });
    }

    /**
     * Hitung sisa budget expense
     */
    public function getRemainingExpenseBudgetAttribute(): float
    {
        return $this->total_expense_budget - $this->total_expense_used;
    }

    /**
     * Hitung persentase budget terpakai
     */
    public function getBudgetUsedPercentageAttribute(): float
    {
        if ($this->total_expense_budget == 0) return 0;
        return ($this->total_expense_used / $this->total_expense_budget) * 100;
    }

    /**
     * Hitung total revenue actual dari daily_incomes
     */
    public function getTotalRevenueActualAttribute(): float
    {
        return DailyIncome::where('property_id', $this->property_id)
            ->whereYear('date', $this->year)
            ->sum('total_revenue');
    }

    /**
     * Forecast: Prediksi bulan habis budget
     */
    public function getForecastedDepletionMonthAttribute(): ?int
    {
        $monthsPassed = now()->year == $this->year ? now()->month : 12;

        if ($monthsPassed == 0) return null;

        $avgMonthlySpending = $this->total_expense_used / $monthsPassed;

        if ($avgMonthlySpending == 0) return null;

        $monthsRemaining = $this->remaining_expense_budget / $avgMonthlySpending;

        return (int) ceil($monthsPassed + $monthsRemaining);
    }

    /**
     * Get budget health status
     */
    public function getBudgetHealthAttribute(): string
    {
        $percentage = $this->budget_used_percentage;

        if ($percentage < 60) return 'healthy';
        if ($percentage < 85) return 'warning';
        return 'critical';
    }
}
