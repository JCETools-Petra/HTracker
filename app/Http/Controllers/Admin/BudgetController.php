<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetDepartment;
use App\Models\BudgetExpense;
use App\Models\BudgetPeriod;
use App\Models\BudgetRevenueTarget;
use App\Models\DailyIncome;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Dashboard - Overview budget period
     */
    public function index(Property $property)
    {
        $budgetPeriods = BudgetPeriod::where('property_id', $property->id)
            ->with(['departments.expenses', 'revenueTargets'])
            ->orderBy('year', 'desc')
            ->get();

        return view('admin.budgets.index', compact('property', 'budgetPeriods'));
    }

    /**
     * Form create budget period baru
     */
    public function create(Property $property)
    {
        $currentYear = now()->year;
        $availableYears = range($currentYear, $currentYear + 5);

        $existingYears = BudgetPeriod::where('property_id', $property->id)
            ->pluck('year')
            ->toArray();

        $availableYears = array_diff($availableYears, $existingYears);

        return view('admin.budgets.create', compact('property', 'availableYears'));
    }

    /**
     * Store budget period baru
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
            'total_revenue_target' => 'required|numeric|min:0',
            'total_expense_budget' => 'required|numeric|min:0',
            'departments' => 'required|array|min:1',
            'departments.*.name' => 'required|string',
            'departments.*.code' => 'required|string',
            'departments.*.allocated_budget' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create budget period
            $target_profit = $validated['total_revenue_target'] - $validated['total_expense_budget'];

            $budgetPeriod = BudgetPeriod::create([
                'property_id' => $property->id,
                'year' => $validated['year'],
                'total_revenue_target' => $validated['total_revenue_target'],
                'total_expense_budget' => $validated['total_expense_budget'],
                'target_profit' => $target_profit,
                'status' => 'draft',
            ]);

            // Create departments
            $sortOrder = 0;
            foreach ($validated['departments'] as $dept) {
                BudgetDepartment::create([
                    'budget_period_id' => $budgetPeriod->id,
                    'name' => $dept['name'],
                    'code' => $dept['code'],
                    'allocated_budget' => $dept['allocated_budget'],
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Initialize revenue targets for 12 months
            for ($month = 1; $month <= 12; $month++) {
                BudgetRevenueTarget::create([
                    'budget_period_id' => $budgetPeriod->id,
                    'month' => $month,
                    'target_amount' => $validated['total_revenue_target'] / 12, // Average distribution
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.budgets.show', ['property' => $property, 'budgetPeriod' => $budgetPeriod])
                ->with('success', "Budget untuk tahun {$validated['year']} berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat budget: ' . $e->getMessage());
        }
    }

    /**
     * Dashboard monitoring budget (show)
     */
    public function show(Property $property, BudgetPeriod $budgetPeriod)
    {
        $budgetPeriod->load([
            'departments.expenses.creator',
            'revenueTargets'
        ]);

        // Calculate summary data
        $summary = [
            'total_allocated' => $budgetPeriod->total_expense_budget,
            'total_used' => $budgetPeriod->total_expense_used,
            'total_remaining' => $budgetPeriod->remaining_expense_budget,
            'usage_percentage' => $budgetPeriod->budget_used_percentage,
            'health_status' => $budgetPeriod->budget_health,
            'forecasted_depletion_month' => $budgetPeriod->forecasted_depletion_month,
        ];

        // Revenue tracking
        $revenueTracking = [
            'target' => $budgetPeriod->total_revenue_target,
            'actual' => $budgetPeriod->total_revenue_actual,
            'variance' => $budgetPeriod->total_revenue_actual - $budgetPeriod->total_revenue_target,
        ];

        // Monthly data for charts
        $monthlyData = $this->getMonthlyChartData($budgetPeriod);

        return view('admin.budgets.show', compact(
            'property',
            'budgetPeriod',
            'summary',
            'revenueTracking',
            'monthlyData'
        ));
    }

    /**
     * Form untuk input expense transaction
     */
    public function createExpense(Property $property, BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->isLocked()) {
            return back()->with('error', 'Budget sudah terkunci, tidak dapat menambah transaksi.');
        }

        $departments = $budgetPeriod->departments()->ordered()->get();

        return view('admin.budgets.expenses.create', compact('property', 'budgetPeriod', 'departments'));
    }

    /**
     * Store expense transaction
     */
    public function storeExpense(Request $request, Property $property, BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->isLocked()) {
            return back()->with('error', 'Budget sudah terkunci.');
        }

        $validated = $request->validate([
            'budget_department_id' => 'required|exists:budget_departments,id',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'receipt_number' => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes' => 'nullable|string',
        ]);

        // Upload receipt if exists
        if ($request->hasFile('receipt_file')) {
            $path = $request->file('receipt_file')->store('budget-receipts', 'public');
            $validated['receipt_file'] = $path;
        }

        $validated['created_by'] = auth()->id();

        BudgetExpense::create($validated);

        return redirect()
            ->route('admin.budgets.show', ['property' => $property, 'budgetPeriod' => $budgetPeriod])
            ->with('success', 'Transaksi pengeluaran berhasil ditambahkan.');
    }

    /**
     * Update budget period (edit department allocations)
     */
    public function update(Request $request, Property $property, BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->isLocked()) {
            return back()->with('error', 'Budget sudah terkunci dan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'total_revenue_target' => 'required|numeric|min:0',
            'total_expense_budget' => 'required|numeric|min:0',
            'departments' => 'required|array',
            'departments.*.id' => 'required|exists:budget_departments,id',
            'departments.*.allocated_budget' => 'required|numeric|min:0',
            'revenue_targets' => 'nullable|array',
            'revenue_targets.*.month' => 'required|integer|between:1,12',
            'revenue_targets.*.target_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update budget period
            $budgetPeriod->update([
                'total_revenue_target' => $validated['total_revenue_target'],
                'total_expense_budget' => $validated['total_expense_budget'],
                'target_profit' => $validated['total_revenue_target'] - $validated['total_expense_budget'],
            ]);

            // Update departments
            foreach ($validated['departments'] as $deptData) {
                BudgetDepartment::where('id', $deptData['id'])
                    ->update(['allocated_budget' => $deptData['allocated_budget']]);
            }

            // Update revenue targets if provided
            if (isset($validated['revenue_targets'])) {
                foreach ($validated['revenue_targets'] as $targetData) {
                    BudgetRevenueTarget::updateOrCreate(
                        [
                            'budget_period_id' => $budgetPeriod->id,
                            'month' => $targetData['month']
                        ],
                        ['target_amount' => $targetData['target_amount']]
                    );
                }
            }

            DB::commit();

            return back()->with('success', 'Budget berhasil diupdate.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update budget: ' . $e->getMessage());
        }
    }

    /**
     * Submit budget untuk approval
     */
    public function submit(Property $property, BudgetPeriod $budgetPeriod)
    {
        if (!$budgetPeriod->isDraft()) {
            return back()->with('error', 'Hanya budget dengan status draft yang bisa disubmit.');
        }

        $budgetPeriod->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Budget berhasil disubmit untuk approval.');
    }

    /**
     * Approve budget (Admin/Owner only)
     */
    public function approve(Property $property, BudgetPeriod $budgetPeriod)
    {
        if (!$budgetPeriod->isSubmitted()) {
            return back()->with('error', 'Hanya budget yang sudah disubmit yang bisa diapprove.');
        }

        $budgetPeriod->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Budget berhasil diapprove.');
    }

    /**
     * Lock budget (Final, tidak bisa diubah lagi)
     */
    public function lock(Property $property, BudgetPeriod $budgetPeriod)
    {
        if (!$budgetPeriod->isApproved()) {
            return back()->with('error', 'Hanya budget yang sudah diapprove yang bisa dilock.');
        }

        $budgetPeriod->update(['status' => 'locked']);

        return back()->with('success', 'Budget berhasil dilock. Budget tidak dapat diubah lagi.');
    }

    /**
     * Reject budget (kembalikan ke draft)
     */
    public function reject(Request $request, Property $property, BudgetPeriod $budgetPeriod)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $budgetPeriod->update([
            'status' => 'draft',
            'notes' => $validated['notes'],
            'submitted_at' => null,
        ]);

        return back()->with('success', 'Budget dikembalikan ke status draft.');
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

    /**
     * Delete expense transaction
     */
    public function destroyExpense(Property $property, BudgetPeriod $budgetPeriod, BudgetExpense $expense)
    {
        if ($budgetPeriod->isLocked()) {
            return back()->with('error', 'Tidak dapat menghapus transaksi pada budget yang terkunci.');
        }

        $expense->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    /**
     * Get monthly chart data for dashboard
     */
    private function getMonthlyChartData(BudgetPeriod $budgetPeriod)
    {
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            // Revenue
            $revenueTarget = $budgetPeriod->revenueTargets()->where('month', $month)->first();
            $revenueActual = DailyIncome::where('property_id', $budgetPeriod->property_id)
                ->whereYear('date', $budgetPeriod->year)
                ->whereMonth('date', $month)
                ->sum('total_revenue');

            // Expenses
            $expenseActual = BudgetExpense::whereIn('budget_department_id', $budgetPeriod->departments->pluck('id'))
                ->whereYear('expense_date', $budgetPeriod->year)
                ->whereMonth('expense_date', $month)
                ->sum('amount');

            $data[] = [
                'month' => $month,
                'month_name' => \Carbon\Carbon::create(null, $month)->format('M'),
                'revenue_target' => $revenueTarget?->target_amount ?? 0,
                'revenue_actual' => $revenueActual,
                'expense_actual' => $expenseActual,
            ];
        }

        return $data;
    }
}
