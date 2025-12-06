<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Laporan P&L (Profit & Loss) - ') }} {{ $property->name }}
            </h2>
            <nav class="flex flex-wrap items-center space-x-2 sm:space-x-3">
                <x-nav-link :href="route('financial.input-actual')" class="ml-3">
                    {{ __('Input Data Aktual') }}
                </x-nav-link>
            </nav>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Period Selection -->
                    <form method="GET" action="{{ route('financial.report') }}" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow">
                        <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-4 md:space-y-0">
                            <div class="flex-1">
                                <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                                <select name="year" id="year" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1">
                                <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                                <select name="month" id="month" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    @foreach($months as $m)
                                        <option value="{{ $m['value'] }}" {{ $month == $m['value'] ? 'selected' : '' }}>
                                            {{ $m['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Tampilkan</button>
                            </div>
                        </div>
                    </form>

                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Periode:</strong> {{ \Carbon\Carbon::create(2000, $month, 1)->format('F') }} {{ $year }}
                        </p>
                    </div>

                    <!-- P&L Report Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-800 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Deskripsi
                                    </th>
                                    <th colspan="3" class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider border-l border-gray-600">
                                        Current Month
                                    </th>
                                    <th colspan="3" class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider border-l border-gray-600">
                                        Year to Date (YTD)
                                    </th>
                                </tr>
                                <tr class="bg-gray-700 dark:bg-gray-800">
                                    <th class="px-6 py-2"></th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-300 uppercase">Actual</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-300 uppercase">Budget</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-300 uppercase border-r border-gray-600">Variance</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-300 uppercase">Actual</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-300 uppercase">Budget</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-300 uppercase">Variance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- REVENUE SECTION -->
                                <tr class="bg-green-50 dark:bg-green-900">
                                    <td colspan="7" class="px-6 py-3 text-sm font-bold text-green-800 dark:text-green-200 uppercase">
                                        REVENUE (PENDAPATAN)
                                    </td>
                                </tr>

                                @foreach($pnlData['categories'] as $category)
                                    @if($category['type'] === 'revenue')
                                        @include('financial.partials.category-row', ['category' => $category])
                                    @endif
                                @endforeach

                                <!-- Total Revenue -->
                                <tr class="bg-green-100 dark:bg-green-800 border-t-2 border-green-600">
                                    <td class="px-6 py-3 text-sm font-bold text-green-900 dark:text-green-100">
                                        TOTAL REVENUE
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-green-900 dark:text-green-100">
                                        Rp {{ number_format($pnlData['totals']['total_revenue']['actual_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-green-900 dark:text-green-100">
                                        Rp {{ number_format($pnlData['totals']['total_revenue']['budget_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold border-r border-gray-400 {{ $pnlData['totals']['total_revenue']['variance_current'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        Rp {{ number_format($pnlData['totals']['total_revenue']['variance_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-green-900 dark:text-green-100">
                                        Rp {{ number_format($pnlData['totals']['total_revenue']['actual_ytd'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-green-900 dark:text-green-100">
                                        Rp {{ number_format($pnlData['totals']['total_revenue']['budget_ytd'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold {{ $pnlData['totals']['total_revenue']['variance_ytd'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        Rp {{ number_format($pnlData['totals']['total_revenue']['variance_ytd'], 0, ',', '.') }}
                                    </td>
                                </tr>

                                <!-- EXPENSES SECTION -->
                                <tr class="bg-red-50 dark:bg-red-900">
                                    <td colspan="7" class="px-6 py-3 text-sm font-bold text-red-800 dark:text-red-200 uppercase">
                                        EXPENSES (PENGELUARAN)
                                    </td>
                                </tr>

                                @foreach($pnlData['categories'] as $category)
                                    @if($category['type'] === 'expense')
                                        @include('financial.partials.category-row', ['category' => $category])
                                    @endif
                                @endforeach

                                <!-- Total Expenses -->
                                <tr class="bg-red-100 dark:bg-red-800 border-t-2 border-red-600">
                                    <td class="px-6 py-3 text-sm font-bold text-red-900 dark:text-red-100">
                                        TOTAL EXPENSES
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-red-900 dark:text-red-100">
                                        Rp {{ number_format($pnlData['totals']['total_expenses']['actual_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-red-900 dark:text-red-100">
                                        Rp {{ number_format($pnlData['totals']['total_expenses']['budget_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold border-r border-gray-400 {{ $pnlData['totals']['total_expenses']['variance_current'] <= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        Rp {{ number_format($pnlData['totals']['total_expenses']['variance_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-red-900 dark:text-red-100">
                                        Rp {{ number_format($pnlData['totals']['total_expenses']['actual_ytd'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-red-900 dark:text-red-100">
                                        Rp {{ number_format($pnlData['totals']['total_expenses']['budget_ytd'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-bold {{ $pnlData['totals']['total_expenses']['variance_ytd'] <= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        Rp {{ number_format($pnlData['totals']['total_expenses']['variance_ytd'], 0, ',', '.') }}
                                    </td>
                                </tr>

                                <!-- GROSS OPERATING PROFIT -->
                                <tr class="bg-blue-100 dark:bg-blue-800 border-t-4 border-blue-600">
                                    <td class="px-6 py-4 text-base font-bold text-blue-900 dark:text-blue-100 uppercase">
                                        GROSS OPERATING PROFIT (GOP)
                                    </td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-blue-900 dark:text-blue-100">
                                        Rp {{ number_format($pnlData['totals']['gross_operating_profit']['actual_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-blue-900 dark:text-blue-100">
                                        Rp {{ number_format($pnlData['totals']['gross_operating_profit']['budget_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-base font-bold border-r border-gray-400 {{ $pnlData['totals']['gross_operating_profit']['variance_current'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        Rp {{ number_format($pnlData['totals']['gross_operating_profit']['variance_current'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-blue-900 dark:text-blue-100">
                                        Rp {{ number_format($pnlData['totals']['gross_operating_profit']['actual_ytd'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-blue-900 dark:text-blue-100">
                                        Rp {{ number_format($pnlData['totals']['gross_operating_profit']['budget_ytd'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-base font-bold {{ $pnlData['totals']['gross_operating_profit']['variance_ytd'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        Rp {{ number_format($pnlData['totals']['gross_operating_profit']['variance_ytd'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
