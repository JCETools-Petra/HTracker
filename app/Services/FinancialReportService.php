<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\FinancialEntry;
use App\Models\DailyIncome;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Generate P&L Report for a specific property, year, and month.
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getPnL(int $propertyId, int $year, int $month): array
    {
        // Get all root categories (top-level) for this property
        $rootCategories = FinancialCategory::forProperty($propertyId)
            ->roots()
            ->with('descendants')
            ->get();

        $result = [
            'property_id' => $propertyId,
            'year' => $year,
            'month' => $month,
            'categories' => [],
            'totals' => [
                'total_revenue' => [
                    'actual_current' => 0,
                    'budget_current' => 0,
                    'variance_current' => 0,
                    'actual_ytd' => 0,
                    'budget_ytd' => 0,
                    'variance_ytd' => 0,
                ],
                'total_expenses' => [
                    'actual_current' => 0,
                    'budget_current' => 0,
                    'variance_current' => 0,
                    'actual_ytd' => 0,
                    'budget_ytd' => 0,
                    'variance_ytd' => 0,
                ],
                'gross_operating_profit' => [
                    'actual_current' => 0,
                    'budget_current' => 0,
                    'variance_current' => 0,
                    'actual_ytd' => 0,
                    'budget_ytd' => 0,
                    'variance_ytd' => 0,
                ],
            ],
        ];

        // Process each root category
        foreach ($rootCategories as $category) {
            $categoryData = $this->processCategoryRecursive($category, $propertyId, $year, $month);
            $result['categories'][] = $categoryData;

            // Accumulate totals
            if ($category->type === 'revenue') {
                $result['totals']['total_revenue']['actual_current'] += $categoryData['actual_current'];
                $result['totals']['total_revenue']['budget_current'] += $categoryData['budget_current'];
                $result['totals']['total_revenue']['actual_ytd'] += $categoryData['actual_ytd'];
                $result['totals']['total_revenue']['budget_ytd'] += $categoryData['budget_ytd'];
            } elseif ($category->type === 'expense') {
                $result['totals']['total_expenses']['actual_current'] += $categoryData['actual_current'];
                $result['totals']['total_expenses']['budget_current'] += $categoryData['budget_current'];
                $result['totals']['total_expenses']['actual_ytd'] += $categoryData['actual_ytd'];
                $result['totals']['total_expenses']['budget_ytd'] += $categoryData['budget_ytd'];
            }
        }

        // Calculate variances for totals
        $result['totals']['total_revenue']['variance_current'] =
            $result['totals']['total_revenue']['actual_current'] - $result['totals']['total_revenue']['budget_current'];
        $result['totals']['total_revenue']['variance_ytd'] =
            $result['totals']['total_revenue']['actual_ytd'] - $result['totals']['total_revenue']['budget_ytd'];

        $result['totals']['total_expenses']['variance_current'] =
            $result['totals']['total_expenses']['actual_current'] - $result['totals']['total_expenses']['budget_current'];
        $result['totals']['total_expenses']['variance_ytd'] =
            $result['totals']['total_expenses']['actual_ytd'] - $result['totals']['total_expenses']['budget_ytd'];

        // Calculate Gross Operating Profit (Revenue - Expenses)
        $result['totals']['gross_operating_profit']['actual_current'] =
            $result['totals']['total_revenue']['actual_current'] - $result['totals']['total_expenses']['actual_current'];
        $result['totals']['gross_operating_profit']['budget_current'] =
            $result['totals']['total_revenue']['budget_current'] - $result['totals']['total_expenses']['budget_current'];
        $result['totals']['gross_operating_profit']['variance_current'] =
            $result['totals']['gross_operating_profit']['actual_current'] - $result['totals']['gross_operating_profit']['budget_current'];

        $result['totals']['gross_operating_profit']['actual_ytd'] =
            $result['totals']['total_revenue']['actual_ytd'] - $result['totals']['total_expenses']['actual_ytd'];
        $result['totals']['gross_operating_profit']['budget_ytd'] =
            $result['totals']['total_revenue']['budget_ytd'] - $result['totals']['total_expenses']['budget_ytd'];
        $result['totals']['gross_operating_profit']['variance_ytd'] =
            $result['totals']['gross_operating_profit']['actual_ytd'] - $result['totals']['gross_operating_profit']['budget_ytd'];

        return $result;
    }

    /**
     * Process a category and its children recursively.
     *
     * @param FinancialCategory $category
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @param int $level
     * @return array
     */
    private function processCategoryRecursive(
        FinancialCategory $category,
        int $propertyId,
        int $year,
        int $month,
        int $level = 0
    ): array {
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
            'variance_current' => 0,
            'actual_ytd' => 0,
            'budget_ytd' => 0,
            'variance_ytd' => 0,
            'children' => [],
        ];

        // If category has children, recursively process them
        if ($category->hasChildren()) {
            foreach ($category->children as $child) {
                $childData = $this->processCategoryRecursive($child, $propertyId, $year, $month, $level + 1);
                $data['children'][] = $childData;

                // Sum up children values
                $data['actual_current'] += $childData['actual_current'];
                $data['budget_current'] += $childData['budget_current'];
                $data['actual_ytd'] += $childData['actual_ytd'];
                $data['budget_ytd'] += $childData['budget_ytd'];
            }
        } else {
            // Leaf node - get actual values
            if ($category->code) {
                // Auto-calculated from DailyIncome
                $values = $this->getAutoCalculatedValues($category->code, $propertyId, $year, $month);
                $data['actual_current'] = $values['current'];
                $data['actual_ytd'] = $values['ytd'];
                $data['budget_current'] = 0; // Auto-calculated categories don't have budget
                $data['budget_ytd'] = 0;
            } else {
                // Manual input from financial_entries
                $values = $this->getManualEntryValues($category->id, $propertyId, $year, $month);
                $data['actual_current'] = $values['actual_current'];
                $data['budget_current'] = $values['budget_current'];
                $data['actual_ytd'] = $values['actual_ytd'];
                $data['budget_ytd'] = $values['budget_ytd'];
            }
        }

        // Calculate variances
        $data['variance_current'] = $data['actual_current'] - $data['budget_current'];
        $data['variance_ytd'] = $data['actual_ytd'] - $data['budget_ytd'];

        return $data;
    }

    /**
     * Get auto-calculated values from DailyIncome based on code.
     *
     * @param string $code
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    private function getAutoCalculatedValues(string $code, int $propertyId, int $year, int $month): array
    {
        $field = match ($code) {
            'ROOM_REV' => 'total_rooms_revenue',
            'FNB_REV' => 'total_fb_revenue',
            default => null,
        };

        if (!$field) {
            return ['current' => 0, 'ytd' => 0];
        }

        // Current month value
        $current = DailyIncome::where('property_id', $propertyId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum($field);

        // Year-to-date value (January to current month)
        $ytd = DailyIncome::where('property_id', $propertyId)
            ->whereYear('date', $year)
            ->whereMonth('date', '<=', $month)
            ->sum($field);

        return [
            'current' => $current ?? 0,
            'ytd' => $ytd ?? 0,
        ];
    }

    /**
     * Get manual entry values from financial_entries.
     *
     * @param int $categoryId
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    private function getManualEntryValues(int $categoryId, int $propertyId, int $year, int $month): array
    {
        // Current month entry
        $currentEntry = FinancialEntry::where('financial_category_id', $categoryId)
            ->where('property_id', $propertyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        // YTD entries (January to current month)
        $ytdEntries = FinancialEntry::where('financial_category_id', $categoryId)
            ->where('property_id', $propertyId)
            ->where('year', $year)
            ->where('month', '<=', $month)
            ->get();

        return [
            'actual_current' => $currentEntry->actual_value ?? 0,
            'budget_current' => $currentEntry->budget_value ?? 0,
            'actual_ytd' => $ytdEntries->sum('actual_value') ?? 0,
            'budget_ytd' => $ytdEntries->sum('budget_value') ?? 0,
        ];
    }

    /**
     * Get categories for input form, grouped by department.
     *
     * @param int $propertyId
     * @return array
     */
    public function getCategoriesForInput(int $propertyId): array
    {
        // Get all root expense categories (departments)
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
     *
     * @param FinancialCategory $category
     * @return array
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
     * Get the full path of a category (parent > child > grandchild).
     *
     * @param FinancialCategory $category
     * @return string
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
     * Save or update financial entry.
     *
     * @param int $propertyId
     * @param int $categoryId
     * @param int $year
     * @param int $month
     * @param float $actualValue
     * @param float $budgetValue
     * @return FinancialEntry
     */
    public function saveEntry(
        int $propertyId,
        int $categoryId,
        int $year,
        int $month,
        float $actualValue = 0,
        float $budgetValue = 0
    ): FinancialEntry {
        return FinancialEntry::updateOrCreate(
            [
                'property_id' => $propertyId,
                'financial_category_id' => $categoryId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'actual_value' => $actualValue,
                'budget_value' => $budgetValue,
            ]
        );
    }

    /**
     * Get chart data for P&L visualization.
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getChartData(int $propertyId, int $year, int $month): array
    {
        $monthlyData = [];

        // Get data for last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = \Carbon\Carbon::create($year, $month, 1)->subMonths($i);
            $pnl = $this->getPnL($propertyId, $date->year, $date->month);

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'revenue_actual' => $pnl['totals']['total_revenue']['actual_current'],
                'revenue_budget' => $pnl['totals']['total_revenue']['budget_current'],
                'expense_actual' => $pnl['totals']['total_expenses']['actual_current'],
                'expense_budget' => $pnl['totals']['total_expenses']['budget_current'],
                'gop_actual' => $pnl['totals']['gross_operating_profit']['actual_current'],
                'gop_budget' => $pnl['totals']['gross_operating_profit']['budget_current'],
            ];
        }

        // Revenue breakdown for current month
        $pnl = $this->getPnL($propertyId, $year, $month);
        $revenueBreakdown = [];
        foreach ($pnl['categories'] as $category) {
            if ($category['type'] === 'revenue' && $category['actual_current'] > 0) {
                $revenueBreakdown[] = [
                    'name' => $category['name'],
                    'value' => $category['actual_current'],
                ];
            }
        }

        // Expense breakdown by department
        $expenseBreakdown = [];
        foreach ($pnl['categories'] as $category) {
            if ($category['type'] === 'expense' && $category['actual_current'] > 0 && $category['level'] === 0) {
                $expenseBreakdown[] = [
                    'name' => $category['name'],
                    'value' => $category['actual_current'],
                ];
            }
        }

        return [
            'monthly_trend' => $monthlyData,
            'revenue_breakdown' => $revenueBreakdown,
            'expense_breakdown' => $expenseBreakdown,
        ];
    }

    /**
     * Get comparative analysis (MoM, YoY).
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getComparativeAnalysis(int $propertyId, int $year, int $month): array
    {
        $currentPnL = $this->getPnL($propertyId, $year, $month);

        // Month-over-Month (previous month)
        $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
        $prevMonthPnL = $this->getPnL($propertyId, $prevMonth->year, $prevMonth->month);

        // Year-over-Year (same month last year)
        $lastYear = $year - 1;
        $lastYearPnL = $this->getPnL($propertyId, $lastYear, $month);

        return [
            'current' => [
                'period' => \Carbon\Carbon::create($year, $month, 1)->format('F Y'),
                'revenue' => $currentPnL['totals']['total_revenue']['actual_current'],
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
                'period' => \Carbon\Carbon::create($lastYear, $month, 1)->format('F Y'),
                'revenue' => $lastYearPnL['totals']['total_revenue']['actual_current'],
                'revenue_change' => $this->calculatePercentageChange(
                    $lastYearPnL['totals']['total_revenue']['actual_current'],
                    $currentPnL['totals']['total_revenue']['actual_current']
                ),
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
     * Calculate percentage change.
     *
     * @param float $oldValue
     * @param float $newValue
     * @return float
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        return (($newValue - $oldValue) / abs($oldValue)) * 100;
    }

    /**
     * Calculate KPIs (Key Performance Indicators).
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getKPIs(int $propertyId, int $year, int $month): array
    {
        $pnl = $this->getPnL($propertyId, $year, $month);

        $revenue = $pnl['totals']['total_revenue']['actual_current'];
        $expense = $pnl['totals']['total_expenses']['actual_current'];
        $gop = $pnl['totals']['gross_operating_profit']['actual_current'];

        // Calculate labor cost (all payroll categories)
        $laborCost = 0;
        foreach ($pnl['categories'] as $category) {
            if ($category['type'] === 'expense') {
                $laborCost += $this->sumPayrollRecursive($category);
            }
        }

        // Get F&B revenue and cost
        $fnbRevenue = 0;
        $fnbCost = 0;
        foreach ($pnl['categories'] as $category) {
            if ($category['code'] === 'FNB_REV') {
                $fnbRevenue = $category['actual_current'];
            }
            if (in_array($category['name'], ['F&B Product (Kitchen)', 'F&B Service'])) {
                // Get COGS from children
                foreach ($category['children'] ?? [] as $child) {
                    if (str_contains(strtolower($child['name']), 'cogs') ||
                        str_contains(strtolower($child['name']), 'cost')) {
                        $fnbCost += $child['actual_current'];
                    }
                }
            }
        }

        // Get room count from property (assuming it's available)
        $property = \App\Models\Property::find($propertyId);
        $roomCount = $property->total_rooms ?? 1;
        $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;

        return [
            'gop_percentage' => $revenue > 0 ? ($gop / $revenue) * 100 : 0,
            'labor_cost_percentage' => $revenue > 0 ? ($laborCost / $revenue) * 100 : 0,
            'labor_cost' => $laborCost,
            'fnb_cost_percentage' => $fnbRevenue > 0 ? ($fnbCost / $fnbRevenue) * 100 : 0,
            'expense_per_available_room' => $roomCount > 0 ? $expense / ($roomCount * $daysInMonth) : 0,
            'revenue_per_available_room' => $roomCount > 0 ? $revenue / ($roomCount * $daysInMonth) : 0,
        ];
    }

    /**
     * Sum all payroll costs recursively.
     *
     * @param array $category
     * @return float
     */
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
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @param int $forecastMonths
     * @return array
     */
    public function getForecast(int $propertyId, int $year, int $month, int $forecastMonths = 3): array
    {
        // Get last 12 months data for trend analysis
        $historicalData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = \Carbon\Carbon::create($year, $month, 1)->subMonths($i);
            $pnl = $this->getPnL($propertyId, $date->year, $date->month);
            $historicalData[] = [
                'revenue' => $pnl['totals']['total_revenue']['actual_current'],
                'expense' => $pnl['totals']['total_expenses']['actual_current'],
                'gop' => $pnl['totals']['gross_operating_profit']['actual_current'],
            ];
        }

        // Simple linear regression forecast
        $forecast = [];
        for ($i = 1; $i <= $forecastMonths; $i++) {
            $date = \Carbon\Carbon::create($year, $month, 1)->addMonths($i);

            // Calculate average growth rate
            $revenueGrowth = $this->calculateAverageGrowth(array_column($historicalData, 'revenue'));
            $expenseGrowth = $this->calculateAverageGrowth(array_column($historicalData, 'expense'));

            $lastRevenue = end($historicalData)['revenue'];
            $lastExpense = end($historicalData)['expense'];

            $forecastRevenue = $lastRevenue * (1 + $revenueGrowth);
            $forecastExpense = $lastExpense * (1 + $expenseGrowth);

            $forecast[] = [
                'month' => $date->format('F Y'),
                'revenue_forecast' => $forecastRevenue,
                'expense_forecast' => $forecastExpense,
                'gop_forecast' => $forecastRevenue - $forecastExpense,
                'confidence' => 'medium', // Simplified confidence level
            ];
        }

        return $forecast;
    }

    /**
     * Calculate average growth rate.
     *
     * @param array $values
     * @return float
     */
    private function calculateAverageGrowth(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $growthRates = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = ($values[$i] - $values[$i - 1]) / $values[$i - 1];
            }
        }

        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
    }

    /**
     * Get budget alerts (categories exceeding budget).
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @param float $threshold
     * @return array
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

    /**
     * Check category and children for budget alerts recursively.
     *
     * @param array $category
     * @param array &$alerts
     * @param float $threshold
     */
    private function checkCategoryAlerts(array $category, array &$alerts, float $threshold): void
    {
        // Only check expense categories with budget
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

        // Check children
        foreach ($category['children'] ?? [] as $child) {
            $this->checkCategoryAlerts($child, $alerts, $threshold);
        }
    }

    /**
     * Get dashboard summary data.
     *
     * @param int $propertyId
     * @param int $year
     * @param int $month
     * @return array
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
}
