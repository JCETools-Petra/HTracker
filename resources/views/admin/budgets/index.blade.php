<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Budget Planner - ') }}{{ $property->name }}
            </h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
                <x-secondary-button onclick="window.history.back()">
                    {{ __('Kembali') }}
                </x-secondary-button>
                @can('manage-data')
                    <a href="{{ route('admin.budgets.create', $property) }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('+ Buat Budget Baru') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-300 p-3 rounded-md border border-green-300 dark:border-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md border border-red-300 dark:border-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Daftar Budget Tahunan</h3>

                    @if($budgetPeriods->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Belum ada budget yang dibuat untuk properti ini.</p>
                            <a href="{{ route('admin.budgets.create', $property) }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 transition">
                                {{ __('Buat Budget Pertama') }}
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tahun</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dibuat</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($budgetPeriods as $budgetPeriod)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $budgetPeriod->year }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($budgetPeriod->status === 'draft')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                        Draft
                                                    </span>
                                                @elseif($budgetPeriod->status === 'approved')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Approved
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        Locked
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $budgetPeriod->created_at->isoFormat('D MMM YYYY') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <a href="{{ route('admin.budgets.show', [$property, $budgetPeriod]) }}"
                                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Edit Budget
                                                </a>
                                                <a href="{{ route('admin.budgets.report', [$property, $budgetPeriod]) }}"
                                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Lihat Laporan
                                                </a>
                                                @if(!$budgetPeriod->isLocked())
                                                    <form action="{{ route('admin.budgets.destroy', [$property, $budgetPeriod]) }}"
                                                          method="POST"
                                                          class="inline-block"
                                                          onsubmit="return confirm('Yakin ingin menghapus budget tahun {{ $budgetPeriod->year }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
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
</x-app-layout>
