<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetCategory;
use App\Models\BudgetDriver;
use App\Models\BudgetPeriod;
use App\Models\BudgetPlan;
use App\Models\DailyIncome;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Menampilkan daftar budget periods untuk sebuah property
     */
    public function index(Property $property)
    {
        $budgetPeriods = BudgetPeriod::where('property_id', $property->id)
            ->orderBy('year', 'desc')
            ->get();

        return view('admin.budgets.index', compact('property', 'budgetPeriods'));
    }

    /**
     * Form untuk membuat budget period baru
     */
    public function create(Property $property)
    {
        // Get available years (current year and next 5 years)
        $currentYear = now()->year;
        $availableYears = range($currentYear, $currentYear + 5);

        // Remove years that already have budgets
        $existingYears = BudgetPeriod::where('property_id', $property->id)
            ->pluck('year')
            ->toArray();

        $availableYears = array_diff($availableYears, $existingYears);

        return view('admin.budgets.create', compact('property', 'availableYears'));
    }

    /**
     * Menyimpan budget period baru
     */
    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'year' => [
                'required',
                'integer',
                'min:' . now()->year,
                Rule::unique('budget_periods')->where('property_id', $property->id)
            ],
        ]);

        DB::beginTransaction();
        try {
            // Create budget period
            $budgetPeriod = BudgetPeriod::create([
                'property_id' => $property->id,
                'year' => $validated['year'],
                'status' => 'draft',
            ]);

            // Initialize budget drivers for all 12 months
            for ($month = 1; $month <= 12; $month++) {
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $validated['year']);

                BudgetDriver::create([
                    'budget_period_id' => $budgetPeriod->id,
                    'month' => $month,
                    'target_occupancy_pct' => 0,
                    'target_adr' => 0,
                    'days_in_month' => $daysInMonth,
                ]);
            }

            // Initialize budget plans for all active categories and 12 months
            $categories = BudgetCategory::active()->get();

            foreach ($categories as $category) {
                for ($month = 1; $month <= 12; $month++) {
                    BudgetPlan::create([
                        'budget_period_id' => $budgetPeriod->id,
                        'budget_category_id' => $category->id,
                        'month' => $month,
                        'amount' => 0,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.budgets.show', ['property' => $property, 'budgetPeriod' => $budgetPeriod])
                ->with('success', "Budget untuk tahun {$validated['year']} berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat budget: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan grid input budget (Excel-like interface)
     */
    public function show(Property $property, BudgetPeriod $budgetPeriod)
    {
        // Load relations
        $budgetPeriod->load(['budgetDrivers', 'budgetPlans.budgetCategory']);

        // Get categories grouped by department and type
        $categories = BudgetCategory::active()
            ->ordered()
            ->get()
            ->groupBy(function ($category) {
                // Group by department first, then type
                return $category->department . '|' . $category->type;
            });

        // Get budget drivers indexed by month
        $drivers = $budgetPeriod->budgetDrivers->keyBy('month');

        // Get budget plans indexed by category_id and month
        $plans = $budgetPeriod->budgetPlans->groupBy('budget_category_id')
            ->map(function ($group) {
                return $group->keyBy('month');
            });

        return view('admin.budgets.show', compact(
            'property',
            'budgetPeriod',
            'categories',
            'drivers',
            'plans'
        ));
    }

    /**
     * Update budget data (drivers dan plans)
     */
    public function update(Request $request, Property $property, BudgetPeriod $budgetPeriod)
    {
        // Check if budget is locked
        if ($budgetPeriod->isLocked()) {
            return back()->with('error', 'Budget sudah terkunci dan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'drivers' => 'required|array',
            'drivers.*.month' => 'required|integer|between:1,12',
            'drivers.*.target_occupancy_pct' => 'nullable|numeric|min:0|max:100',
            'drivers.*.target_adr' => 'nullable|numeric|min:0',
            'plans' => 'required|array',
            'plans.*.budget_category_id' => 'required|exists:budget_categories,id',
            'plans.*.month' => 'required|integer|between:1,12',
            'plans.*.amount' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Update budget drivers
            foreach ($validated['drivers'] as $driverData) {
                BudgetDriver::updateOrCreate(
                    [
                        'budget_period_id' => $budgetPeriod->id,
                        'month' => $driverData['month'],
                    ],
                    [
                        'target_occupancy_pct' => $driverData['target_occupancy_pct'] ?? 0,
                        'target_adr' => $driverData['target_adr'] ?? 0,
                    ]
                );
            }

            // Update budget plans
            foreach ($validated['plans'] as $planData) {
                BudgetPlan::updateOrCreate(
                    [
                        'budget_period_id' => $budgetPeriod->id,
                        'budget_category_id' => $planData['budget_category_id'],
                        'month' => $planData['month'],
                    ],
                    [
                        'amount' => $planData['amount'] ?? 0,
                    ]
                );
            }

            DB::commit();

            return back()->with('success', 'Budget berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan budget: ' . $e->getMessage());
        }
    }

    /**
     * Update status budget (draft -> approved -> locked)
     */
    public function updateStatus(Request $request, Property $property, BudgetPeriod $budgetPeriod)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,approved,locked',
        ]);

        $budgetPeriod->update(['status' => $validated['status']]);

        return back()->with('success', 'Status budget berhasil diubah menjadi ' . $validated['status'] . '.');
    }

    /**
     * Laporan P&L: Budget vs Actual
     */
    public function report(Property $property, BudgetPeriod $budgetPeriod, Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $budgetPeriod->year;

        // Get budget data for selected month
        $budgetPlans = BudgetPlan::where('budget_period_id', $budgetPeriod->id)
            ->where('month', $month)
            ->with('budgetCategory')
            ->get()
            ->keyBy('budget_category_id');

        // Get actual data from daily_incomes for selected month
        $actualData = $this->getActualData($property->id, $year, $month);

        // Get budget driver for the month
        $budgetDriver = BudgetDriver::where('budget_period_id', $budgetPeriod->id)
            ->where('month', $month)
            ->first();

        // Get categories for display
        $categories = BudgetCategory::active()->ordered()->get();

        // Build comparison data
        $comparisonData = $this->buildComparisonData($categories, $budgetPlans, $actualData);

        return view('admin.budgets.report', compact(
            'property',
            'budgetPeriod',
            'month',
            'year',
            'comparisonData',
            'budgetDriver'
        ));
    }

    /**
     * Get actual data from daily_incomes table
     */
    private function getActualData($propertyId, $year, $month)
    {
        $dailyIncomes = DailyIncome::where('property_id', $propertyId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Map daily_incomes columns to budget categories
        return [
            // Room Revenue
            '4010' => $dailyIncomes->sum('offline_room_income'),
            '4020' => $dailyIncomes->sum('online_room_income'),
            '4030' => $dailyIncomes->sum('ta_income'),
            '4040' => $dailyIncomes->sum('gov_income'),
            '4050' => $dailyIncomes->sum('corp_income'),

            // F&B Revenue
            '4111' => $dailyIncomes->sum('breakfast_income'),
            '4112' => $dailyIncomes->sum('lunch_income'),
            '4113' => $dailyIncomes->sum('dinner_income'),

            // Other Revenue
            '4210' => $dailyIncomes->sum('mice_room_income'),
            '4290' => $dailyIncomes->sum('others_income'),
        ];
    }

    /**
     * Build comparison data array
     */
    private function buildComparisonData($categories, $budgetPlans, $actualData)
    {
        $data = [];
        $totalRevenueBudget = 0;
        $totalRevenueActual = 0;
        $totalExpenseBudget = 0;
        $totalExpenseActual = 0;

        foreach ($categories as $category) {
            $budgetAmount = $budgetPlans->get($category->id)?->amount ?? 0;
            $actualAmount = $actualData[$category->code] ?? 0;
            $variance = $actualAmount - $budgetAmount;
            $variancePct = $budgetAmount != 0 ? ($variance / $budgetAmount) * 100 : 0;

            $data[] = [
                'category' => $category,
                'budget' => $budgetAmount,
                'actual' => $actualAmount,
                'variance' => $variance,
                'variance_pct' => $variancePct,
            ];

            // Accumulate totals
            if ($category->isRevenue()) {
                $totalRevenueBudget += $budgetAmount;
                $totalRevenueActual += $actualAmount;
            } elseif ($category->isExpense()) {
                $totalExpenseBudget += $budgetAmount;
                $totalExpenseActual += $actualAmount;
            }
        }

        // Add summary rows
        $data['summary'] = [
            'total_revenue_budget' => $totalRevenueBudget,
            'total_revenue_actual' => $totalRevenueActual,
            'total_expense_budget' => $totalExpenseBudget,
            'total_expense_actual' => $totalExpenseActual,
            'net_profit_budget' => $totalRevenueBudget - $totalExpenseBudget,
            'net_profit_actual' => $totalRevenueActual - $totalExpenseActual,
        ];

        return $data;
    }

    /**
     * Delete budget period
     */
    public function destroy(Property $property, BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->isLocked()) {
            return back()->with('error', 'Budget yang sudah terkunci tidak dapat dihapus.');
        }

        $year = $budgetPeriod->year;
        $budgetPeriod->delete();

        return redirect()
            ->route('admin.budgets.index', $property)
            ->with('success', "Budget tahun {$year} berhasil dihapus.");
    }
}
