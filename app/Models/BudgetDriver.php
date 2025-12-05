<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_period_id',
        'month',
        'target_occupancy_pct',
        'target_adr',
        'days_in_month',
    ];

    protected $casts = [
        'target_occupancy_pct' => 'decimal:2',
        'target_adr' => 'decimal:2',
    ];

    /**
     * Relasi ke Budget Period
     */
    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
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
     * Hitung projected room revenue berdasarkan occupancy dan ADR
     */
    public function calculateRoomRevenue($totalRooms): float
    {
        if (!$totalRooms || !$this->days_in_month) {
            return 0;
        }

        $occupancyDecimal = $this->target_occupancy_pct / 100;
        $roomsSold = $totalRooms * $this->days_in_month * $occupancyDecimal;

        return $roomsSold * $this->target_adr;
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
