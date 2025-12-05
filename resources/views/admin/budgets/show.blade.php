<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Budget Dashboard - ') }}{{ $property->name }} ({{ $budgetPeriod->year }})
            </h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
                <x-secondary-button onclick="window.location.href='{{ route('admin.budgets.index', $property) }}'">
                    {{ __('Kembali') }}
                </x-secondary-button>
                @if(!$budgetPeriod->isLocked())
                    <a href="{{ route('admin.budgets.expenses.create', [$property, $budgetPeriod]) }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 transition">
                        {{ __('+ Input Expense') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="font-medium text-sm text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-300 p-3 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Status & Actions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>
                        @if($budgetPeriod->isDraft())
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                        @elseif($budgetPeriod->isSubmitted())
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Submitted</span>
                        @elseif($budgetPeriod->isApproved())
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Locked</span>
                        @endif
                    </div>

                    <div class="flex space-x-2">
                        @if($budgetPeriod->isDraft())
                            <form action="{{ route('admin.budgets.submit', [$property, $budgetPeriod]) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs rounded hover:bg-blue-500 uppercase font-semibold">
                                    Submit untuk Approval
                                </button>
                            </form>
                        @elseif($budgetPeriod->isSubmitted())
                            @can('manage-data')
                                <form action="{{ route('admin.budgets.approve', [$property, $budgetPeriod]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-xs rounded hover:bg-green-500 uppercase font-semibold">
                                        Approve Budget
                                    </button>
                                </form>
                                <button onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                                        class="px-4 py-2 bg-red-600 text-white text-xs rounded hover:bg-red-500 uppercase font-semibold">
                                    Reject
                                </button>
                            @endcan
                        @elseif($budgetPeriod->isApproved())
                            @can('manage-data')
                                <form action="{{ route('admin.budgets.lock', [$property, $budgetPeriod]) }}" method="POST" onsubmit="return confirm('Yakin lock budget? Tidak bisa diubah lagi!')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white text-xs rounded hover:bg-gray-500 uppercase font-semibold">
                                        Lock Budget
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Allocated -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Budget Allocated</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($summary['total_allocated'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- Total Used -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Budget Used</div>
                    <div class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">
                        Rp {{ number_format($summary['total_used'], 0, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ number_format($summary['usage_percentage'], 1) }}% terpakai
                    </div>
                </div>

                <!-- Remaining -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Remaining Budget</div>
                    <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        Rp {{ number_format($summary['total_remaining'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- Health Status -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Budget Health</div>
                    <div class="mt-2 flex items-center space-x-2">
                        @if($summary['health_status'] === 'healthy')
                            <span class="text-4xl">🟢</span>
                            <span class="text-2xl font-bold text-green-600">Healthy</span>
                        @elseif($summary['health_status'] === 'warning')
                            <span class="text-4xl">⚠️</span>
                            <span class="text-2xl font-bold text-yellow-600">Warning</span>
                        @else
                            <span class="text-4xl">🔴</span>
                            <span class="text-2xl font-bold text-red-600">Critical</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Forecast & Revenue Tracking -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Forecast -->
                <div class="bg-blue-50 dark:bg-blue-900/20 overflow-hidden shadow-sm sm:rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-3">📊 Forecast</h3>
                    @if($summary['forecasted_depletion_month'])
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Dengan rata-rata pengeluaran saat ini, budget diperkirakan akan habis pada:
                        </p>
                        <p class="mt-2 text-2xl font-bold text-blue-900 dark:text-blue-200">
                            Bulan ke-{{ $summary['forecasted_depletion_month'] }}
                        </p>
                        <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                            ({{ \Carbon\Carbon::now()->addMonths($summary['forecasted_depletion_month'] - \Carbon\Carbon::now()->month)->format('F Y') }})
                        </p>
                    @else
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Belum ada data pengeluaran untuk forecast.
                        </p>
                    @endif
                </div>

                <!-- Revenue Tracking -->
                <div class="bg-green-50 dark:bg-green-900/20 overflow-hidden shadow-sm sm:rounded-lg p-6 border border-green-200 dark:border-green-700">
                    <h3 class="font-semibold text-green-800 dark:text-green-300 mb-3">💰 Revenue Tracking</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-green-700 dark:text-green-300">Target:</span>
                            <span class="font-semibold text-green-900 dark:text-green-200">
                                Rp {{ number_format($revenueTracking['target'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-green-700 dark:text-green-300">Actual:</span>
                            <span class="font-semibold text-green-900 dark:text-green-200">
                                Rp {{ number_format($revenueTracking['actual'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-green-200 dark:border-green-700 flex justify-between">
                            <span class="text-sm font-semibold text-green-700 dark:text-green-300">Variance:</span>
                            <span class="font-bold {{ $revenueTracking['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $revenueTracking['variance'] >= 0 ? '+' : '' }}Rp {{ number_format(abs($revenueTracking['variance']), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Breakdown -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Budget per Department</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Department</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Allocated</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Used</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Remaining</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Usage</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($budgetPeriod->departments as $dept)
                                    @php
                                        $used = $dept->expenses->sum('amount');
                                        $remaining = $dept->allocated_budget - $used;
                                        $percentage = $dept->allocated_budget > 0 ? ($used / $dept->allocated_budget) * 100 : 0;

                                        if ($percentage < 60) {
                                            $statusColor = 'green';
                                            $statusIcon = '🟢';
                                        } elseif ($percentage < 85) {
                                            $statusColor = 'yellow';
                                            $statusIcon = '⚠️';
                                        } else {
                                            $statusColor = 'red';
                                            $statusIcon = '🔴';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $dept->name }}
                                            <span class="ml-2 text-xs text-gray-500">({{ $dept->code }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($dept->allocated_budget, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-orange-600">
                                            Rp {{ number_format($used, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-green-600">
                                            Rp {{ number_format($remaining, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <div class="flex items-center justify-center">
                                                <div class="w-full max-w-xs">
                                                    <div class="flex items-center">
                                                        <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                                            <div class="bg-{{ $statusColor }}-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                                        </div>
                                                        <span class="text-xs font-semibold">{{ number_format($percentage, 1) }}%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-2xl">
                                            {{ $statusIcon }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Expenses -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Expenses</h3>
                        @if(!$budgetPeriod->isLocked())
                            <a href="{{ route('admin.budgets.expenses.create', [$property, $budgetPeriod]) }}"
                               class="text-sm text-blue-600 hover:text-blue-800">
                                View All →
                            </a>
                        @endif
                    </div>

                    @php
                        $recentExpenses = $budgetPeriod->expenses()->with(['department', 'creator'])->latest()->take(10)->get();
                    @endphp

                    @if($recentExpenses->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                            Belum ada transaksi pengeluaran. Klik "Input Expense" untuk menambah.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deskripsi</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jumlah</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Input By</th>
                                        @if(!$budgetPeriod->isLocked())
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentExpenses as $expense)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $expense->expense_date->format('d M Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $expense->department->name }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                {{ $expense->description }}
                                                @if($expense->category)
                                                    <span class="ml-2 text-xs text-gray-500">({{ $expense->category }})</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-gray-100">
                                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $expense->creator->name }}
                                            </td>
                                            @if(!$budgetPeriod->isLocked())
                                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                    <form action="{{ route('admin.budgets.expenses.destroy', [$property, $budgetPeriod, $expense]) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Yakin hapus transaksi ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Reject Budget</h3>
                <form action="{{ route('admin.budgets.reject', [$property, $budgetPeriod]) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan Reject:</label>
                        <textarea name="notes"
                                  rows="4"
                                  class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                  required
                                  placeholder="Jelaskan alasan reject dan apa yang perlu diperbaiki..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button"
                                onclick="document.getElementById('rejectModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Reject Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
