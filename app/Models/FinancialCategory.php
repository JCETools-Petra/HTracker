<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id',
        'parent_id',
        'name',
        'code',
        'type',
        'is_payroll',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_payroll' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the property that owns this category.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the parent category (for hierarchical structure).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'parent_id');
    }

    /**
     * Get all child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(FinancialCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all descendants (children, grandchildren, etc.) recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all financial entries for this category.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }

    /**
     * Check if this category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if this category is a leaf node (no children).
     */
    public function isLeaf(): bool
    {
        return !$this->hasChildren();
    }

    /**
     * Check if this category should have manual input.
     * Only leaf nodes without code can have manual input.
     */
    public function allowsManualInput(): bool
    {
        return $this->isLeaf() && empty($this->code);
    }

    /**
     * Scope to get only root categories (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    /**
     * Scope to get categories by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get payroll categories.
     */
    public function scopePayroll($query)
    {
        return $query->where('is_payroll', true);
    }

    /**
     * Scope to filter by property.
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
