<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Budget Management - ') }}{{ $property->name }}
            </h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
                <x-secondary-button onclick="window.history.back()">
                    {{ __('Kembali') }}
                </x-secondary-button>
                @can('manage-data')
                    <a href="{{ route('admin.budgets.create', $property) }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 transition">
                        {{ __('+ Budget Baru') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-300 p-3 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Daftar Budget Tahunan</h3>

                    @if($budgetPeriods->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Belum ada budget yang dibuat untuk properti ini.</p>
                            <a href="{{ route('admin.budgets.create', $property) }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-500 transition">
                                {{ __('Buat Budget Pertama') }}
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($budgetPeriods as $period)
                                @php
                                    $totalUsed = $period->departments->sum(function($dept) {
                                        return $dept->expenses->sum('amount');
                                    });
                                    $remaining = $period->total_expense_budget - $totalUsed;
                                    $percentage = $period->total_expense_budget > 0 ? ($totalUsed / $period->total_expense_budget) * 100 : 0;

                                    if ($percentage < 60) {
                                        $healthColor = 'green';
                                        $healthText = 'Sehat';
                                    } elseif ($percentage < 85) {
                                        $healthColor = 'yellow';
                                        $healthText = 'Warning';
                                    } else {
                                        $healthColor = 'red';
                                        $healthText = 'Critical';
                                    }
                                @endphp

                                <div class="border dark:border-gray-700 rounded-lg p-6 hover:shadow-lg transition">
                                    <div class="flex justify-between items-start mb-4">
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $period->year }}</h4>
                                        @if($period->status === 'draft')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                                        @elseif($period->status === 'submitted')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Submitted</span>
                                        @elseif($period->status === 'approved')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Locked</span>
                                        @endif
                                    </div>

                                    <div class="space-y-3 mb-4">
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Target Revenue</p>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($period->total_revenue_target, 0, ',', '.') }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Expense Budget</p>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($period->total_expense_budget, 0, ',', '.') }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Budget Used</p>
                                            <div class="flex items-center space-x-2">
                                                <p class="text-lg font-semibold text-{{ $healthColor }}-600">Rp {{ number_format($totalUsed, 0, ',', '.') }}</p>
                                                <span class="text-xs text-{{ $healthColor }}-600">({{ number_format($percentage, 1) }}%)</span>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Remaining</p>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mb-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-{{ $healthColor }}-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.budgets.show', [$property, $period]) }}"
                                           class="flex-1 text-center px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-500 transition">
                                            Dashboard
                                        </a>
                                        @if(!$period->isLocked())
                                            <form action="{{ route('admin.budgets.destroy', [$property, $period]) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Yakin hapus budget {{ $period->year }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-500 transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
