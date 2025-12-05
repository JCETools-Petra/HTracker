<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('P&L Report: Budget vs Actual - ') }}{{ $property->name }}
            </h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
                <x-secondary-button onclick="window.location.href='{{ route('admin.budgets.index', $property) }}'">
                    {{ __('Kembali') }}
                </x-secondary-button>
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 transition">
                    Print Report
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Month Selector -->
            <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('admin.budgets.report', [$property, $budgetPeriod]) }}" class="flex items-end space-x-4">
                    <div>
                        <x-input-label for="month" :value="__('Pilih Bulan')" />
                        <select id="month" name="month" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <x-primary-button type="submit">
                        {{ __('Filter') }}
                    </x-primary-button>
                </form>
            </div>

            <!-- Performance Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                @php
                    $summary = $comparisonData['summary'];
                    $revenueVariance = $summary['total_revenue_actual'] - $summary['total_revenue_budget'];
                    $revenueVariancePct = $summary['total_revenue_budget'] != 0 ? ($revenueVariance / $summary['total_revenue_budget']) * 100 : 0;
                    $expenseVariance = $summary['total_expense_actual'] - $summary['total_expense_budget'];
                    $expenseVariancePct = $summary['total_expense_budget'] != 0 ? ($expenseVariance / $summary['total_expense_budget']) * 100 : 0;
                    $profitVariance = $summary['net_profit_actual'] - $summary['net_profit_budget'];
                @endphp

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total Revenue</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($summary['total_revenue_actual'], 0, ',', '.') }}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">vs Budget: </span>
                            <span class="ml-2 font-semibold {{ $revenueVariance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $revenueVariance >= 0 ? '+' : '' }}{{ number_format($revenueVariancePct, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total Expenses</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($summary['total_expense_actual'], 0, ',', '.') }}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">vs Budget: </span>
                            <span class="ml-2 font-semibold {{ $expenseVariance <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $expenseVariance >= 0 ? '+' : '' }}{{ number_format($expenseVariancePct, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Net Profit</div>
                        <div class="text-2xl font-bold {{ $summary['net_profit_actual'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            Rp {{ number_format($summary['net_profit_actual'], 0, ',', '.') }}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">vs Budget: </span>
                            <span class="ml-2 font-semibold {{ $profitVariance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $profitVariance >= 0 ? '+' : '' }}Rp {{ number_format(abs($profitVariance), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Drivers Info -->
            @if($budgetDriver)
            <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Budget Assumptions ({{ \Carbon\Carbon::create(null, $month)->format('F') }} {{ $year }})</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700 dark:text-blue-400">Target Occupancy:</span>
                        <span class="ml-2 font-semibold text-blue-900 dark:text-blue-200">{{ number_format($budgetDriver->target_occupancy_pct, 2) }}%</span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-400">Target ADR:</span>
                        <span class="ml-2 font-semibold text-blue-900 dark:text-blue-200">Rp {{ number_format($budgetDriver->target_adr, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-400">Days in Month:</span>
                        <span class="ml-2 font-semibold text-blue-900 dark:text-blue-200">{{ $budgetDriver->days_in_month }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-400">Projected RevPAR:</span>
                        <span class="ml-2 font-semibold text-blue-900 dark:text-blue-200">
                            Rp {{ number_format(($budgetDriver->target_occupancy_pct / 100) * $budgetDriver->target_adr, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
            @endif

            <!-- P&L Comparison Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                        Profit & Loss Statement - {{ \Carbon\Carbon::create(null, $month)->format('F') }} {{ $year }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Account
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Budget
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actual
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Variance (Rp)
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Variance (%)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @php
                                    $currentType = null;
                                    $currentDepartment = null;
                                @endphp

                                @foreach($comparisonData as $key => $row)
                                    @if($key === 'summary')
                                        @continue
                                    @endif

                                    @php
                                        $category = $row['category'];
                                    @endphp

                                    <!-- Section Header for Type Change -->
                                    @if($category->type !== $currentType)
                                        @php $currentType = $category->type; @endphp
                                        <tr class="bg-{{ $category->type === 'revenue' ? 'green' : 'red' }}-50 dark:bg-{{ $category->type === 'revenue' ? 'green' : 'red' }}-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-bold text-{{ $category->type === 'revenue' ? 'green' : 'red' }}-800 dark:text-{{ $category->type === 'revenue' ? 'green' : 'red' }}-300 uppercase">
                                                {{ $category->type === 'revenue' ? 'REVENUE' : 'EXPENSES' }}
                                            </td>
                                        </tr>
                                    @endif

                                    <!-- Department Header -->
                                    @if($category->department !== $currentDepartment)
                                        @php $currentDepartment = $category->department; @endphp
                                        <tr class="bg-gray-100 dark:bg-gray-700">
                                            <td colspan="5" class="px-6 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">
                                                {{ $category->department }}
                                            </td>
                                        </tr>
                                    @endif

                                    <!-- Account Row -->
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $category->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($row['budget'], 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($row['actual'], 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $row['variance'] >= 0 ? ($category->isRevenue() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') : ($category->isRevenue() ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400') }}">
                                            {{ $row['variance'] >= 0 ? '+' : '' }}Rp {{ number_format(abs($row['variance']), 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $row['variance'] >= 0 ? ($category->isRevenue() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') : ($category->isRevenue() ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400') }}">
                                            {{ $row['variance_pct'] >= 0 ? '+' : '' }}{{ number_format($row['variance_pct'], 1) }}%
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Summary Totals -->
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        TOTAL REVENUE
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($summary['total_revenue_budget'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($summary['total_revenue_actual'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right {{ $revenueVariance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $revenueVariance >= 0 ? '+' : '' }}Rp {{ number_format(abs($revenueVariance), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right {{ $revenueVariance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $revenueVariance >= 0 ? '+' : '' }}{{ number_format($revenueVariancePct, 1) }}%
                                    </td>
                                </tr>

                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        TOTAL EXPENSES
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($summary['total_expense_budget'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($summary['total_expense_actual'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right {{ $expenseVariance <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $expenseVariance >= 0 ? '+' : '' }}Rp {{ number_format(abs($expenseVariance), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right {{ $expenseVariance <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $expenseVariance >= 0 ? '+' : '' }}{{ number_format($expenseVariancePct, 1) }}%
                                    </td>
                                </tr>

                                <tr class="bg-gray-200 dark:bg-gray-600 font-bold text-lg">
                                    <td class="px-6 py-4 text-gray-900 dark:text-gray-100">
                                        NET PROFIT
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($summary['net_profit_budget'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right {{ $summary['net_profit_actual'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        Rp {{ number_format($summary['net_profit_actual'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right {{ $profitVariance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $profitVariance >= 0 ? '+' : '' }}Rp {{ number_format(abs($profitVariance), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right {{ $profitVariance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        @if($summary['net_profit_budget'] != 0)
                                            {{ $profitVariance >= 0 ? '+' : '' }}{{ number_format(($profitVariance / $summary['net_profit_budget']) * 100, 1) }}%
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style media="print">
        @page {
            size: landscape;
            margin: 1cm;
        }

        nav, header .flex > *:not(h2), button {
            display: none !important;
        }

        .dark\:bg-gray-800,
        .dark\:bg-gray-700 {
            background-color: white !important;
            color: black !important;
        }
    </style>
    @endpush
</x-app-layout>
