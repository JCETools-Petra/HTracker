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
}
