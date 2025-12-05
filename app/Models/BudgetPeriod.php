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
        'status',
    ];

    /**
     * Relasi ke Property
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relasi ke Budget Plans
     */
    public function budgetPlans(): HasMany
    {
        return $this->hasMany(BudgetPlan::class);
    }

    /**
     * Relasi ke Budget Drivers
     */
    public function budgetDrivers(): HasMany
    {
        return $this->hasMany(BudgetDriver::class);
    }

    /**
     * Scope untuk filter berdasarkan property
     */
    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Check apakah budget masih draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
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
}
