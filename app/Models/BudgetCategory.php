<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'code',
        'name',
        'type',
        'department',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Property (nullable untuk kategori global)
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relasi ke parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'parent_id');
    }

    /**
     * Relasi ke child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(BudgetCategory::class, 'parent_id');
    }

    /**
     * Relasi ke Budget Plans
     */
    public function budgetPlans(): HasMany
    {
        return $this->hasMany(BudgetPlan::class);
    }

    /**
     * Scope untuk filter berdasarkan tipe
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope untuk filter berdasarkan department
     */
    public function scopeOfDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope untuk kategori aktif saja
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk sorting berdasarkan sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check apakah kategori ini revenue
     */
    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    /**
     * Check apakah kategori ini expense
     */
    public function isExpense(): bool
    {
        return in_array($this->type, ['expense_fixed', 'expense_variable', 'payroll']);
    }
}
