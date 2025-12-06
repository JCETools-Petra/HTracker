<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinancialController extends Controller
{
    protected $financialService;

    public function __construct(FinancialReportService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Show property selection page for admin.
     */
    public function selectProperty()
    {
        $properties = Property::orderBy('name')->get();
        return view('admin.financial.select-property', compact('properties'));
    }

    /**
     * Show the actual input form for monthly expenses (Admin version).
     */
    public function showInputActual(Request $request, Property $property)
    {
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

        return view('admin.financial.input-actual', compact(
            'property',
            'year',
            'month',
            'departments',
            'existingEntries'
        ));
    }

    /**
     * Store the actual monthly expenses (Admin version).
     */
    public function storeInputActual(Request $request, Property $property)
    {
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

        return redirect()->route('admin.financial.input-actual', [
            'property' => $property->id,
            'year' => $validated['year'],
            'month' => $validated['month']
        ])->with('success', 'Data berhasil disimpan untuk ' . $property->name);
    }

    /**
     * Show the P&L report (Admin version).
     */
    public function showReport(Request $request, Property $property)
    {
        // Get current month and year or from request
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        // Get P&L data
        $pnlData = $this->financialService->getPnL($property->id, $year, $month);

        // Generate month options for dropdown
        $months = collect(range(1, 12))->map(function ($m) {
            return [
                'value' => $m,
                'name' => Carbon::create()->month($m)->format('F')
            ];
        });

        // Generate year options (current year ± 2 years)
        $currentYear = Carbon::now()->year;
        $years = range($currentYear - 2, $currentYear + 2);

        return view('admin.financial.report', compact(
            'property',
            'year',
            'month',
            'pnlData',
            'months',
            'years'
        ));
    }

    /**
     * Show budget input form for annual budget (Admin version).
     */
    public function showInputBudget(Request $request, Property $property)
    {
        // Get year from request or use next year
        $year = $request->input('year', Carbon::now()->addYear()->year);

        // Get categories grouped by department
        $departments = $this->financialService->getCategoriesForInput($property->id);

        // Get existing budget entries for this year
        $existingEntries = \App\Models\FinancialEntry::where('property_id', $property->id)
            ->where('year', $year)
            ->get()
            ->groupBy('financial_category_id')
            ->map(function ($entries) {
                // Sum all budget values for the year
                return $entries->sum('budget_value');
            });

        return view('admin.financial.input-budget', compact(
            'property',
            'year',
            'departments',
            'existingEntries'
        ));
    }

    /**
     * Store the annual budget (Admin version).
     */
    public function storeInputBudget(Request $request, Property $property)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'entries' => 'required|array',
            'entries.*.category_id' => 'required|exists:financial_categories,id',
            'entries.*.budget_value' => 'required|numeric|min:0',
        ]);

        // Distribute annual budget across all 12 months
        foreach ($validated['entries'] as $entry) {
            $monthlyBudget = $entry['budget_value'] / 12;

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

        return redirect()->route('admin.financial.input-budget', [
            'property' => $property->id,
            'year' => $validated['year']
        ])->with('success', 'Budget tahunan berhasil disimpan untuk ' . $property->name);
    }
}
