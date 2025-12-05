<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_period_id',
        'name',
        'code',
        'allocated_budget',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'allocated_budget' => 'decimal:2',
    ];

    /**
     * Relasi ke Budget Period
     */
    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    /**
     * Relasi ke Expenses
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(BudgetExpense::class);
    }

    /**
     * Hitung total expense yang sudah dipakai
     */
    public function getTotalUsedAttribute(): float
    {
        return $this->expenses->sum('amount');
    }

    /**
     * Hitung sisa budget department
     */
    public function getRemainingBudgetAttribute(): float
    {
        return $this->allocated_budget - $this->total_used;
    }

    /**
     * Hitung persentase budget terpakai
     */
    public function getUsedPercentageAttribute(): float
    {
        if ($this->allocated_budget == 0) return 0;
        return ($this->total_used / $this->allocated_budget) * 100;
    }

    /**
     * Get department health status
     */
    public function getHealthStatusAttribute(): string
    {
        $percentage = $this->used_percentage;

        if ($percentage < 60) return 'healthy';
        if ($percentage < 85) return 'warning';
        return 'critical';
    }

    /**
     * Scope untuk sorting berdasarkan sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
