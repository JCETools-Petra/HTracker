<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinancialController extends Controller
{
    protected $financialService;

    public function __construct(FinancialReportService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Show the actual input form for monthly expenses.
     */
    public function showInputActual(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        // Get current month and year or from request
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        // Get categories grouped by department
        $departments = $this->financialService->getCategoriesForInput($property->id);

        // Get existing entries for this month to pre-fill the form
        $existingEntries = \App\Models\FinancialEntry::where('property_id', $property->id)
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('financial_category_id');

        return view('financial.input-actual', compact(
            'property',
            'year',
            'month',
            'departments',
            'existingEntries'
        ));
    }

    /**
     * Store the actual monthly expenses.
     */
    public function storeInputActual(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'entries' => 'required|array',
            'entries.*.category_id' => 'required|exists:financial_categories,id',
            'entries.*.actual_value' => 'required|numeric|min:0',
            'entries.*.budget_value' => 'nullable|numeric|min:0',
        ]);

        // Save or update each entry
        foreach ($validated['entries'] as $entry) {
            $this->financialService->saveEntry(
                $property->id,
                $entry['category_id'],
                $validated['year'],
                $validated['month'],
                $entry['actual_value'],
                $entry['budget_value'] ?? 0
            );
        }

        return redirect()->route('property.financial.input-actual', [
            'year' => $validated['year'],
            'month' => $validated['month']
        ])->with('success', 'Data berhasil disimpan.');
    }

    /**
     * Show the P&L report.
     */
    public function showReport(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        // Get current month and year or from request
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        // Get P&L data
        $pnlData = $this->financialService->getPnL($property->id, $year, $month);

        // Get additional data for enhanced features
        $chartData = $this->financialService->getChartData($property->id, $year, $month);
        $kpis = $this->financialService->getKPIs($property->id, $year, $month);
        $comparative = $this->financialService->getComparativeAnalysis($property->id, $year, $month);
        $alerts = $this->financialService->getBudgetAlerts($property->id, $year, $month);
        $forecast = $this->financialService->getForecast($property->id, $year, $month);

        // Generate month options for dropdown
        $months = collect(range(1, 12))->map(function ($m) {
            return [
                'value' => $m,
                'name' => Carbon::create(2000, $m, 1)->format('F')
            ];
        });

        // Generate year options (current year ± 2 years)
        $currentYear = Carbon::now()->year;
        $years = range($currentYear - 2, $currentYear + 2);

        return view('financial.report', compact(
            'property',
            'year',
            'month',
            'pnlData',
            'chartData',
            'kpis',
            'comparative',
            'alerts',
            'forecast',
            'months',
            'years'
        ));
    }

    /**
     * Export P&L report to Excel.
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $fileName = 'PnL_' . $property->name . '_' . Carbon::create($year, $month, 1)->format('Y-m') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PnLExport($property->id, $property->name, $year, $month),
            $fileName
        );
    }

    /**
     * Export P&L report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $pnlData = $this->financialService->getPnL($property->id, $year, $month);
        $kpis = $this->financialService->getKPIs($property->id, $year, $month);
        $comparative = $this->financialService->getComparativeAnalysis($property->id, $year, $month);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('financial.pdf.pnl-report', compact(
            'property',
            'year',
            'month',
            'pnlData',
            'kpis',
            'comparative'
        ));

        $fileName = 'PnL_' . $property->name . '_' . Carbon::create($year, $month, 1)->format('Y-m') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Show financial dashboard.
     */
    public function showDashboard(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $dashboardData = $this->financialService->getDashboardSummary($property->id, $year, $month);
        $chartData = $this->financialService->getChartData($property->id, $year, $month);
        $alerts = $this->financialService->getBudgetAlerts($property->id, $year, $month);

        return view('financial.dashboard', compact(
            'property',
            'year',
            'month',
            'dashboardData',
            'chartData',
            'alerts'
        ));
    }

    /**
     * Copy data from previous month (bulk input feature).
     */
    public function copyFromPreviousMonth(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        // Get previous month
        $date = Carbon::create($validated['year'], $validated['month'], 1);
        $prevMonth = $date->copy()->subMonth();

        // Get all entries from previous month
        $prevEntries = \App\Models\FinancialEntry::where('property_id', $property->id)
            ->where('year', $prevMonth->year)
            ->where('month', $prevMonth->month)
            ->get();

        // Copy to current month
        $copiedCount = 0;
        foreach ($prevEntries as $entry) {
            $this->financialService->saveEntry(
                $property->id,
                $entry->financial_category_id,
                $validated['year'],
                $validated['month'],
                $entry->actual_value,
                $entry->budget_value
            );
            $copiedCount++;
        }

        return redirect()->route('property.financial.input-actual', [
            'year' => $validated['year'],
            'month' => $validated['month']
        ])->with('success', "Berhasil menyalin $copiedCount data dari bulan sebelumnya.");
    }

    /**
     * Show budget input form for annual budget.
     */
    public function showInputBudget(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        // Get year from request or use next year
        $year = $request->input('year', Carbon::now()->addYear()->year);

        // Get categories grouped by department
        $departments = $this->financialService->getCategoriesForInput($property->id);

        // Get existing budget entries for this year (using month 1 as annual budget)
        $existingEntries = \App\Models\FinancialEntry::where('property_id', $property->id)
            ->where('year', $year)
            ->get()
            ->groupBy('financial_category_id')
            ->map(function ($entries) {
                // Sum all budget values for the year
                return $entries->sum('budget_value');
            });

        return view('financial.input-budget', compact(
            'property',
            'year',
            'departments',
            'existingEntries'
        ));
    }

    /**
     * Store the annual budget.
     */
    public function storeInputBudget(Request $request)
    {
        $user = Auth::user();
        $property = $user->property;

        if (!$property) {
            abort(403, 'Akun Anda tidak terikat pada properti manapun.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'entries' => 'required|array',
            'entries.*.category_id' => 'required|exists:financial_categories,id',
            'entries.*.budget_value' => 'required|numeric|min:0',
        ]);

        // Distribute annual budget across all 12 months
        foreach ($validated['entries'] as $entry) {
            $monthlyBudget = $entry['budget_value'] / 12; // Distribute equally

            for ($month = 1; $month <= 12; $month++) {
                $this->financialService->saveEntry(
                    $property->id,
                    $entry['category_id'],
                    $validated['year'],
                    $month,
                    0, // actual value (will be filled later)
                    $monthlyBudget
                );
            }
        }

        return redirect()->route('property.financial.input-budget', ['year' => $validated['year']])
            ->with('success', 'Budget tahunan berhasil disimpan dan didistribusikan ke 12 bulan.');
    }
}
