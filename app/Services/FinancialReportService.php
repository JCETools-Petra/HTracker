<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\FinancialEntry;
use App\Models\DailyIncome;
use App\Models\Booking;
use App\Models\DailyOccupancy;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportService
{
    /**
     * Generate P&L Report for a specific property, year, and month.
     * Optimized with eager loading to prevent N+1 queries.
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getPnL(int $propertyId, int $year, int $month): array
    {
        // 1. Get structure: All root categories with descendants
        $rootCategories = FinancialCategory::forProperty($propertyId)
            ->roots()
            ->with('descendants')
            ->get();

        // 2. Data Fetching Strategy: Batch fetch all needed data at once
        
        // Fetch Current Month Entries
        $entriesCurrent = FinancialEntry::where('property_id', $propertyId)
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('financial_category_id');

        // Fetch YTD Entries (Aggregated)
        $entriesYtd = FinancialEntry::where('property_id', $propertyId)
            ->where('year', $year)
            ->where('month', '<=', $month)
            ->selectRaw('financial_category_id, SUM(actual_value) as total_actual, SUM(budget_value) as total_budget, SUM(forecast_value) as total_forecast')
            ->groupBy('financial_category_id')
            ->get()
            ->keyBy('financial_category_id');

        // Fetch Auto-Calculated Data (Current Month)
        $bookingsCurrent = Booking::where('property_id', $propertyId)
            ->where('status', 'Booking Pasti')
            ->whereYear('event_date', $year)
            ->whereMonth('event_date', $month)
            ->sum('total_price');

        $dailyIncomeCurrent = DailyIncome::where('property_id', $propertyId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->first([
                DB::raw('SUM(total_rooms_revenue) as room_rev'),
                DB::raw('SUM(beverage_income) as beverage_rev'),
                DB::raw('SUM(breakfast_income) as breakfast_rev'),
                DB::raw('SUM(lunch_income) as lunch_rev'),
                DB::raw('SUM(dinner_income) as dinner_rev'),
                DB::raw('SUM(package_income) as package_rev'),
                DB::raw('SUM(rental_area_income) as rental_rev'),
                DB::raw('SUM(others_income) as others_rev')
            ]);

        // Fetch Auto-Calculated Data (YTD)
        $bookingsYtd = Booking::where('property_id', $propertyId)
            ->where('status', 'Booking Pasti')
            ->whereYear('event_date', $year)
            ->whereMonth('event_date', '<=', $month)
            ->sum('total_price');

        $dailyIncomeYtd = DailyIncome::where('property_id', $propertyId)
            ->whereYear('date', $year)
            ->whereMonth('date', '<=', $month)
            ->first([
                DB::raw('SUM(total_rooms_revenue) as room_rev'),
                DB::raw('SUM(beverage_income) as beverage_rev'),
                DB::raw('SUM(breakfast_income) as breakfast_rev'),
                DB::raw('SUM(lunch_income) as lunch_rev'),
                DB::raw('SUM(dinner_income) as dinner_rev'),
                DB::raw('SUM(package_income) as package_rev'),
                DB::raw('SUM(rental_area_income) as rental_rev'),
                DB::raw('SUM(others_income) as others_rev')
            ]);

        // Prepare Context for Recursive Function
        $context = [
            'entries_current' => $entriesCurrent,
            'entries_ytd' => $entriesYtd,
            'auto_values' => [
                'current' => [
                    'ROOM_REV' => $dailyIncomeCurrent->room_rev ?? 0,
                    'BEV_REV' => $dailyIncomeCurrent->beverage_rev ?? 0,
                    'BREAKFAST_REV' => $dailyIncomeCurrent->breakfast_rev ?? 0,
                    'LUNCH_REV' => $dailyIncomeCurrent->lunch_rev ?? 0,
                    'DINNER_REV' => $dailyIncomeCurrent->dinner_rev ?? 0,
                    'PACKAGE_REV' => $dailyIncomeCurrent->package_rev ?? 0,
                    'MICE_REV' => $bookingsCurrent ?? 0,
                    'RENTAL_REV' => $dailyIncomeCurrent->rental_rev ?? 0,
                    'OTHERS_REV' => $dailyIncomeCurrent->others_rev ?? 0,
                ],
                'ytd' => [
                    'ROOM_REV' => $dailyIncomeYtd->room_rev ?? 0,
                    'BEV_REV' => $dailyIncomeYtd->beverage_rev ?? 0,
                    'BREAKFAST_REV' => $dailyIncomeYtd->breakfast_rev ?? 0,
                    'LUNCH_REV' => $dailyIncomeYtd->lunch_rev ?? 0,
                    'DINNER_REV' => $dailyIncomeYtd->dinner_rev ?? 0,
                    'PACKAGE_REV' => $dailyIncomeYtd->package_rev ?? 0,
                    'MICE_REV' => $bookingsYtd ?? 0,
                    'RENTAL_REV' => $dailyIncomeYtd->rental_rev ?? 0,
                    'OTHERS_REV' => $dailyIncomeYtd->others_rev ?? 0,
                ]
            ]
        ];

        $result = [
            'property_id' => $propertyId,
            'year' => $year,
            'month' => $month,
            'categories' => [],
            'totals' => $this->getEmptyTotalsStructure(),
        ];

        // Process Categories
        foreach ($rootCategories as $category) {
            $categoryData = $this->processCategoryRecursive($category, $context);
            $result['categories'][] = $categoryData;

            // Accumulate Totals
            if ($category->type === 'revenue') {
                $this->accumulateTotals($result['totals']['total_revenue'], $categoryData);
            } elseif ($category->type === 'expense') {
                $this->accumulateTotals($result['totals']['total_expenses'], $categoryData);
            }
        }

        // Final Calculations
        $this->calculateVariances($result['totals']);

        return $result;
    }

    /**
     * Recursive processor that uses in-memory context data instead of DB queries.
     */
    /**
     * Recursive processor that uses in-memory context data instead of DB queries.
     */
    private function processCategoryRecursive(FinancialCategory $category, array $context, int $level = 0): array
    {
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
            'type' => $category->type,
            'is_payroll' => $category->is_payroll,
            'level' => $level,
            'has_children' => $category->hasChildren(),
            'allows_input' => $category->allowsManualInput(),
            'actual_current' => 0,
            'budget_current' => 0,
            'forecast_current' => 0, // [BARU]
            'actual_ytd' => 0,
            'budget_ytd' => 0,
            'forecast_ytd' => 0,     // [BARU]
            'variance_current' => 0,
            'variance_ytd' => 0,
            'children' => [],
        ];

        if ($category->hasChildren()) {
            foreach ($category->children as $child) {
                $childData = $this->processCategoryRecursive($child, $context, $level + 1);
                $data['children'][] = $childData;

                // Accumulate children values
                $data['actual_current'] += $childData['actual_current'];
                $data['budget_current'] += $childData['budget_current'];
                $data['forecast_current'] += $childData['forecast_current']; // [BARU]
                
                $data['actual_ytd'] += $childData['actual_ytd'];
                $data['budget_ytd'] += $childData['budget_ytd'];
                $data['forecast_ytd'] += $childData['forecast_ytd'];         // [BARU]
            }
        } else {
            // Leaf Node Logic
            if ($category->code && isset($context['auto_values']['current'][$category->code])) {
                // Use Auto Calculated Values
                $data['actual_current'] = $context['auto_values']['current'][$category->code];
                $data['actual_ytd'] = $context['auto_values']['ytd'][$category->code];
                $data['budget_current'] = 0; 
                $data['budget_ytd'] = 0;
                // Auto values usually don't have manual forecast input
                $data['forecast_current'] = 0; 
                $data['forecast_ytd'] = 0;
            } else {
                // Use Manual Entries from Map
                $entryCurrent = $context['entries_current']->get($category->id);
                $entryYtd = $context['entries_ytd']->get($category->id);

                $data['actual_current'] = $entryCurrent ? $entryCurrent->actual_value : 0;
                $data['budget_current'] = $entryCurrent ? $entryCurrent->budget_value : 0;
                $data['forecast_current'] = $entryCurrent ? $entryCurrent->forecast_value : 0; // [BARU]
                
                $data['actual_ytd'] = $entryYtd ? $entryYtd->total_actual : 0;
                $data['budget_ytd'] = $entryYtd ? $entryYtd->total_budget : 0;
                // Pastikan query YTD di getPnL sudah diupdate untuk mengambil SUM(forecast_value) as total_forecast
                $data['forecast_ytd'] = $entryYtd ? ($entryYtd->total_forecast ?? 0) : 0;      // [BARU]
            }
        }

        // Calculate Variances (Actual vs Budget)
        $data['variance_current'] = $data['actual_current'] - $data['budget_current'];
        $data['variance_ytd'] = $data['actual_ytd'] - $data['budget_ytd'];

        return $data;
    }

    /**
     * Get chart data optimized using raw aggregations.
     */
    public function getChartData(int $propertyId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->subMonths(11)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // 1. Data Manual (Financial Entries) - Mengambil Budget & Actual (terutama Expense)
        // Filter: Hanya ambil yang BUKAN kategori otomatis agar tidak double counting
        $entriesData = FinancialEntry::query()
            ->join('financial_categories', 'financial_entries.financial_category_id', '=', 'financial_categories.id')
            ->where('financial_entries.property_id', $propertyId)
            ->whereBetween(DB::raw("CAST(CONCAT(year, '-', month, '-01') AS DATE)"), [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where(function($q) {
                $q->whereNull('financial_categories.code')
                  ->orWhereNotIn('financial_categories.code', ['ROOM_REV', 'FNB_REV', 'MICE_REV']);
            })
            ->selectRaw('
                year, 
                month, 
                financial_categories.type,
                SUM(actual_value) as total_actual, 
                SUM(budget_value) as total_budget
            ')
            ->groupBy('year', 'month', 'financial_categories.type')
            ->get();

        // 2. Data Otomatis: Daily Income (Room & F&B Revenue)
        $dailyIncomeData = DailyIncome::where('property_id', $propertyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                YEAR(date) as year, 
                MONTH(date) as month, 
                SUM(total_rooms_revenue + total_fb_revenue) as total_auto_revenue
            ')
            ->groupByRaw('YEAR(date), MONTH(date)')
            ->get();

        // 3. Data Otomatis: MICE (Bookings)
        $bookingData = Booking::where('property_id', $propertyId)
            ->where('status', 'Booking Pasti')
            ->whereBetween('event_date', [$startDate, $endDate])
            ->selectRaw('
                YEAR(event_date) as year, 
                MONTH(event_date) as month, 
                SUM(total_price) as total_mice_revenue
            ')
            ->groupByRaw('YEAR(event_date), MONTH(event_date)')
            ->get();

        // 4. Gabungkan Semua Data (Merge)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::create($year, $month, 1)->subMonths($i);
            $y = $date->year;
            $m = $date->month;

            // Ambil Data Manual
            $manualRev = $entriesData->where('year', $y)->where('month', $m)->where('type', 'revenue')->first();
            $manualExp = $entriesData->where('year', $y)->where('month', $m)->where('type', 'expense')->first();

            // Ambil Data Otomatis
            $autoDaily = $dailyIncomeData->where('year', $y)->where('month', $m)->first();
            $autoMice = $bookingData->where('year', $y)->where('month', $m)->first();

            // Hitung Total
            // Revenue = Manual + Auto Room/F&B + Auto MICE
            $revenueActual = ($manualRev->total_actual ?? 0) 
                           + ($autoDaily->total_auto_revenue ?? 0) 
                           + ($autoMice->total_mice_revenue ?? 0);
            
            // Budget (Biasanya hanya ada di kategori manual, kecuali ada sistem budget auto)
            $revenueBudget = ($manualRev->total_budget ?? 0); 

            $expenseActual = ($manualExp->total_actual ?? 0);
            $expenseBudget = ($manualExp->total_budget ?? 0);

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'revenue_actual' => $revenueActual,
                'revenue_budget' => $revenueBudget,
                'expense_actual' => $expenseActual,
                'expense_budget' => $expenseBudget,
                'gop_actual' => $revenueActual - $expenseActual,
                'gop_budget' => $revenueBudget - $expenseBudget,
            ];
        }

        // Breakdown tetap menggunakan logika PnL bulan ini
        $currentPnL = $this->getPnL($propertyId, $year, $month);

        return [
            'monthly_trend' => $monthlyData,
            'revenue_breakdown' => $this->extractChartBreakdown($currentPnL, 'revenue'),
            'expense_breakdown' => $this->extractChartBreakdown($currentPnL, 'expense'),
        ];
    }

    /**
     * Helper to extract chart breakdown from PnL data.
     */
    private function extractChartBreakdown(array $pnlData, string $type): array
    {
        $breakdown = [];
        foreach ($pnlData['categories'] as $category) {
            if ($category['type'] === $type && $category['actual_current'] > 0) {
                $breakdown[] = [
                    'name' => $category['name'],
                    'value' => $category['actual_current'],
                ];
            }
        }
        return $breakdown;
    }

    /**
     * Save or update financial entry safely.
     * Prevents overwriting actuals when updating budget and vice versa.
     */
    public function saveEntry(
        int $propertyId,
        int $categoryId,
        int $year,
        int $month,
        ?float $actualValue = null,
        ?float $budgetValue = null,
        ?float $forecastValue = null // Parameter baru
    ): FinancialEntry {
        $entry = FinancialEntry::firstOrNew([
            'property_id' => $propertyId,
            'financial_category_id' => $categoryId,
            'year' => $year,
            'month' => $month,
        ]);
    
        if (!is_null($actualValue)) $entry->actual_value = $actualValue;
        if (!is_null($budgetValue)) $entry->budget_value = $budgetValue;
        if (!is_null($forecastValue)) $entry->forecast_value = $forecastValue; // Simpan forecast
    
        if (!$entry->exists) {
            $entry->actual_value = $entry->actual_value ?? 0;
            $entry->budget_value = $entry->budget_value ?? 0;
            $entry->forecast_value = $entry->forecast_value ?? 0;
        }
    
        $entry->save();
        return $entry;
    }

    /**
     * Get categories for input form, grouped by department.
     */
    public function getCategoriesForInput(int $propertyId): array
    {
        $departments = FinancialCategory::forProperty($propertyId)
            ->where('type', 'expense')
            ->roots()
            ->with('descendants')
            ->get();

        $result = [];

        foreach ($departments as $department) {
            $inputCategories = $this->getInputCategoriesRecursive($department);

            if (count($inputCategories) > 0) {
                $result[] = [
                    'department' => $department->name,
                    'categories' => $inputCategories,
                ];
            }
        }

        return $result;
    }

    /**
     * Get leaf categories that allow manual input recursively.
     */
    private function getInputCategoriesRecursive(FinancialCategory $category): array
    {
        $categories = [];

        if ($category->allowsManualInput()) {
            $categories[] = [
                'id' => $category->id,
                'name' => $category->name,
                'full_path' => $this->getCategoryPath($category),
                'is_payroll' => $category->is_payroll,
            ];
        }

        foreach ($category->children as $child) {
            $categories = array_merge($categories, $this->getInputCategoriesRecursive($child));
        }

        return $categories;
    }

    /**
     * Get the full path of a category.
     */
    private function getCategoryPath(FinancialCategory $category): string
    {
        $path = [$category->name];
        $current = $category;

        while ($current->parent) {
            array_unshift($path, $current->parent->name);
            $current = $current->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get comparative analysis (MoM, YoY) with MICE breakdown.
     */
    public function getComparativeAnalysis(int $propertyId, int $year, int $month): array
    {
        $currentPnL = $this->getPnL($propertyId, $year, $month);

        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
        $prevMonthPnL = $this->getPnL($propertyId, $prevMonth->year, $prevMonth->month);

        $lastYear = $year - 1;
        $lastYearPnL = $this->getPnL($propertyId, $lastYear, $month);

        // Helper to find MICE value
        $getMiceValue = function ($pnlData) {
            return $this->findCategoryValueRecursive($pnlData['categories'], 'MICE_REV');
        };

        $currentMice = $getMiceValue($currentPnL);
        $prevMice = $getMiceValue($prevMonthPnL);
        $lastYearMice = $getMiceValue($lastYearPnL);

        return [
            'current' => [
                'period' => Carbon::create($year, $month, 1)->format('F Y'),
                'revenue' => $currentPnL['totals']['total_revenue']['actual_current'],
                'mice' => $currentMice,
                'expense' => $currentPnL['totals']['total_expenses']['actual_current'],
                'gop' => $currentPnL['totals']['gross_operating_profit']['actual_current'],
            ],
            'mom' => [
                'period' => $prevMonth->format('F Y'),
                'revenue' => $prevMonthPnL['totals']['total_revenue']['actual_current'],
                'revenue_change' => $this->calculatePercentageChange(
                    $prevMonthPnL['totals']['total_revenue']['actual_current'],
                    $currentPnL['totals']['total_revenue']['actual_current']
                ),
                'mice' => $prevMice,
                'mice_change' => $this->calculatePercentageChange($prevMice, $currentMice),
                'expense' => $prevMonthPnL['totals']['total_expenses']['actual_current'],
                'expense_change' => $this->calculatePercentageChange(
                    $prevMonthPnL['totals']['total_expenses']['actual_current'],
                    $currentPnL['totals']['total_expenses']['actual_current']
                ),
                'gop' => $prevMonthPnL['totals']['gross_operating_profit']['actual_current'],
                'gop_change' => $this->calculatePercentageChange(
                    $prevMonthPnL['totals']['gross_operating_profit']['actual_current'],
                    $currentPnL['totals']['gross_operating_profit']['actual_current']
                ),
            ],
            'yoy' => [
                'period' => Carbon::create($lastYear, $month, 1)->format('F Y'),
                'revenue' => $lastYearPnL['totals']['total_revenue']['actual_current'],
                'revenue_change' => $this->calculatePercentageChange(
                    $lastYearPnL['totals']['total_revenue']['actual_current'],
                    $currentPnL['totals']['total_revenue']['actual_current']
                ),
                'mice' => $lastYearMice,
                'mice_change' => $this->calculatePercentageChange($lastYearMice, $currentMice),
                'expense' => $lastYearPnL['totals']['total_expenses']['actual_current'],
                'expense_change' => $this->calculatePercentageChange(
                    $lastYearPnL['totals']['total_expenses']['actual_current'],
                    $currentPnL['totals']['total_expenses']['actual_current']
                ),
                'gop' => $lastYearPnL['totals']['gross_operating_profit']['actual_current'],
                'gop_change' => $this->calculatePercentageChange(
                    $lastYearPnL['totals']['gross_operating_profit']['actual_current'],
                    $currentPnL['totals']['gross_operating_profit']['actual_current']
                ),
            ],
        ];
    }

    /**
     * Helper to find value by category CODE recursively
     */
    private function findCategoryValueRecursive(array $categories, string $code): float
    {
        foreach ($categories as $category) {
            if (($category['code'] ?? '') === $code) {
                return $category['actual_current'];
            }
            if (!empty($category['children'])) {
                $val = $this->findCategoryValueRecursive($category['children'], $code);
                if ($val > 0) return $val;
            }
        }
        return 0;
    }

    /**
     * Calculate percentage change helper.
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        return (($newValue - $oldValue) / abs($oldValue)) * 100;
    }

    /**
     * Calculate KPIs.
     * UPDATED: Disamakan dengan Logic Dashboard Admin (RevPAR = Pure Room Revenue / Available Rooms).
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getKPIs(int $propertyId, int $year, int $month): array
    {
        $pnl = $this->getPnL($propertyId, $year, $month);
    
        $totalRevenue = $pnl['totals']['total_revenue']['actual_current'];
        $expense = $pnl['totals']['total_expenses']['actual_current'];
        $gop = $pnl['totals']['gross_operating_profit']['actual_current'];
    
        // 1. Cari Room Revenue Murni
        $roomRevenue = 0;
        $findRoomRevenueRecursive = function($categories) use (&$findRoomRevenueRecursive, &$roomRevenue) {
            foreach ($categories as $category) {
                if (($category['code'] ?? '') === 'ROOM_REV') {
                    $roomRevenue += $category['actual_current'];
                }
                if (!empty($category['children'])) {
                    $findRoomRevenueRecursive($category['children']);
                }
            }
        };
        $findRoomRevenueRecursive($pnl['categories']);
    
        // 2. Logic F&B & Labor
        $laborCost = 0;
        $fnbRevenue = 0;
        $fnbCost = 0;
    
        foreach ($pnl['categories'] as $category) {
            if ($category['type'] === 'expense') {
                $laborCost += $this->sumPayrollRecursive($category);
            }
            if (($category['code'] ?? '') === 'FNB_REV') {
                $fnbRevenue = $category['actual_current'];
            }
            if (in_array($category['name'], ['F&B Product (Kitchen)', 'F&B Service', 'Food & Beverage'])) {
                foreach ($category['children'] ?? [] as $child) {
                    if (str_contains(strtolower($child['name']), 'cogs') || 
                        str_contains(strtolower($child['name']), 'cost')) {
                        $fnbCost += $child['actual_current'];
                    }
                }
            }
        }
    
        $property = \App\Models\Property::find($propertyId);
        $roomCount = $property->total_rooms ?? 1;
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $availableRooms = $roomCount * $daysInMonth;
    
        // ==========================================
        // LOGIC BARU: CPOR (Cost Per Occupied Room)
        // ==========================================
        
        // Ambil total kamar terjual dari tabel DailyOccupancy
        $occupiedRooms = DailyOccupancy::where('property_id', $propertyId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('occupied_rooms');
    
        return [
            'gop_percentage' => $totalRevenue > 0 ? ($gop / $totalRevenue) * 100 : 0,
            'labor_cost_percentage' => $totalRevenue > 0 ? ($laborCost / $totalRevenue) * 100 : 0,
            'labor_cost' => $laborCost,
            'fnb_cost_percentage' => $fnbRevenue > 0 ? ($fnbCost / $fnbRevenue) * 100 : 0,
            'expense_per_available_room' => $availableRooms > 0 ? $expense / $availableRooms : 0,
            'revenue_per_available_room' => $availableRooms > 0 ? $roomRevenue / $availableRooms : 0,
            
            // Metric Baru
            'total_occupied_rooms' => $occupiedRooms,
            'cost_per_occupied_room' => $occupiedRooms > 0 ? $expense / $occupiedRooms : 0,
        ];
    }

    private function sumPayrollRecursive(array $category): float
    {
        $total = 0;
        if ($category['is_payroll']) {
            $total += $category['actual_current'];
        }
        foreach ($category['children'] ?? [] as $child) {
            $total += $this->sumPayrollRecursive($child);
        }
        return $total;
    }

    /**
     * Get forecast based on historical trends.
     */
    public function getForecast(int $propertyId, int $year, int $month, int $forecastMonths = 3): array
    {
        // Get last 12 months for trend analysis using getChartData (optimized) logic 
        $chartData = $this->getChartData($propertyId, $year, $month);
        $historicalData = [];
        
        foreach ($chartData['monthly_trend'] as $data) {
            $historicalData[] = [
                'revenue' => $data['revenue_actual'],
                'expense' => $data['expense_actual'],
            ];
        }

        $forecast = [];
        for ($i = 1; $i <= $forecastMonths; $i++) {
            $date = Carbon::create($year, $month, 1)->addMonths($i);

            $revenueGrowth = $this->calculateAverageGrowth(array_column($historicalData, 'revenue'));
            $expenseGrowth = $this->calculateAverageGrowth(array_column($historicalData, 'expense'));

            $lastRevenue = end($historicalData)['revenue'];
            $lastExpense = end($historicalData)['expense'];

            $forecastRevenue = $lastRevenue * (1 + $revenueGrowth);
            $forecastExpense = $lastExpense * (1 + $expenseGrowth);

            $historicalData[] = ['revenue' => $forecastRevenue, 'expense' => $forecastExpense];

            $forecast[] = [
                'month' => $date->format('F Y'),
                'revenue_forecast' => $forecastRevenue,
                'expense_forecast' => $forecastExpense,
                'gop_forecast' => $forecastRevenue - $forecastExpense,
                'confidence' => 'medium',
            ];
        }

        return $forecast;
    }

    private function calculateAverageGrowth(array $values): float
    {
        if (count($values) < 2) return 0;
        $growthRates = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = ($values[$i] - $values[$i - 1]) / $values[$i - 1];
            }
        }
        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
    }

    /**
     * Get budget alerts.
     */
    public function getBudgetAlerts(int $propertyId, int $year, int $month, float $threshold = 10): array
    {
        $pnl = $this->getPnL($propertyId, $year, $month);
        $alerts = [];

        foreach ($pnl['categories'] as $category) {
            $this->checkCategoryAlerts($category, $alerts, $threshold);
        }

        return $alerts;
    }

    private function checkCategoryAlerts(array $category, array &$alerts, float $threshold): void
    {
        if ($category['type'] === 'expense' && $category['budget_current'] > 0) {
            $variance = $category['actual_current'] - $category['budget_current'];
            $variancePercentage = ($variance / $category['budget_current']) * 100;

            if ($variancePercentage > $threshold) {
                $alerts[] = [
                    'category_name' => $category['name'],
                    'actual' => $category['actual_current'],
                    'budget' => $category['budget_current'],
                    'variance' => $variance,
                    'variance_percentage' => $variancePercentage,
                    'level' => $variancePercentage > 20 ? 'high' : 'medium',
                ];
            }
        }

        foreach ($category['children'] ?? [] as $child) {
            $this->checkCategoryAlerts($child, $alerts, $threshold);
        }
    }

    /**
     * Get dashboard summary data.
     */
    public function getDashboardSummary(int $propertyId, int $year, int $month): array
    {
        $pnl = $this->getPnL($propertyId, $year, $month);
        $kpis = $this->getKPIs($propertyId, $year, $month);
        $comparative = $this->getComparativeAnalysis($propertyId, $year, $month);
        $alerts = $this->getBudgetAlerts($propertyId, $year, $month);

        return [
            'current_month' => [
                'revenue' => $pnl['totals']['total_revenue']['actual_current'],
                'expense' => $pnl['totals']['total_expenses']['actual_current'],
                'gop' => $pnl['totals']['gross_operating_profit']['actual_current'],
                'gop_percentage' => $kpis['gop_percentage'],
            ],
            'ytd' => [
                'revenue' => $pnl['totals']['total_revenue']['actual_ytd'],
                'expense' => $pnl['totals']['total_expenses']['actual_ytd'],
                'gop' => $pnl['totals']['gross_operating_profit']['actual_ytd'],
            ],
            'budget_achievement' => [
                'revenue' => $pnl['totals']['total_revenue']['budget_current'] > 0
                    ? ($pnl['totals']['total_revenue']['actual_current'] / $pnl['totals']['total_revenue']['budget_current']) * 100
                    : 0,
                'expense' => $pnl['totals']['total_expenses']['budget_current'] > 0
                    ? ($pnl['totals']['total_expenses']['actual_current'] / $pnl['totals']['total_expenses']['budget_current']) * 100
                    : 0,
            ],
            'trends' => [
                'mom_revenue_change' => $comparative['mom']['revenue_change'],
                'yoy_revenue_change' => $comparative['yoy']['revenue_change'],
            ],
            'alerts_count' => count($alerts),
            'high_alerts_count' => count(array_filter($alerts, fn($a) => $a['level'] === 'high')),
        ];
    }

    // --- Helper Functions ---

    private function getEmptyTotalsStructure(): array
    {
        return [
            'total_revenue' => [
                'actual_current' => 0, 'budget_current' => 0, 'variance_current' => 0,
                'actual_ytd' => 0, 'budget_ytd' => 0, 'variance_ytd' => 0,
            ],
            'total_expenses' => [
                'actual_current' => 0, 'budget_current' => 0, 'variance_current' => 0,
                'actual_ytd' => 0, 'budget_ytd' => 0, 'variance_ytd' => 0,
            ],
            'gross_operating_profit' => [
                'actual_current' => 0, 'budget_current' => 0, 'variance_current' => 0,
                'actual_ytd' => 0, 'budget_ytd' => 0, 'variance_ytd' => 0,
            ],
        ];
    }

    private function accumulateTotals(array &$target, array $source): void
    {
        $target['actual_current'] += $source['actual_current'];
        $target['budget_current'] += $source['budget_current'];
        $target['actual_ytd'] += $source['actual_ytd'];
        $target['budget_ytd'] += $source['budget_ytd'];
    }

    private function calculateVariances(array &$totals): void
    {
        // Revenue & Expenses
        foreach (['total_revenue', 'total_expenses'] as $type) {
            $totals[$type]['variance_current'] = $totals[$type]['actual_current'] - $totals[$type]['budget_current'];
            $totals[$type]['variance_ytd'] = $totals[$type]['actual_ytd'] - $totals[$type]['budget_ytd'];
        }

        // GOP
        $totals['gross_operating_profit']['actual_current'] = $totals['total_revenue']['actual_current'] - $totals['total_expenses']['actual_current'];
        $totals['gross_operating_profit']['budget_current'] = $totals['total_revenue']['budget_current'] - $totals['total_expenses']['budget_current'];
        $totals['gross_operating_profit']['variance_current'] = $totals['gross_operating_profit']['actual_current'] - $totals['gross_operating_profit']['budget_current'];

        $totals['gross_operating_profit']['actual_ytd'] = $totals['total_revenue']['actual_ytd'] - $totals['total_expenses']['actual_ytd'];
        $totals['gross_operating_profit']['budget_ytd'] = $totals['total_revenue']['budget_ytd'] - $totals['total_expenses']['budget_ytd'];
        $totals['gross_operating_profit']['variance_ytd'] = $totals['gross_operating_profit']['actual_ytd'] - $totals['gross_operating_profit']['budget_ytd'];
    }
}