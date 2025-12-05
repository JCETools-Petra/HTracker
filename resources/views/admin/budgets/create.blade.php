<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Buat Budget Baru - ') }}{{ $property->name }}
            </h2>
            <x-secondary-button onclick="window.history.back()">
                {{ __('Kembali') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Inisialisasi Budget Tahunan</h3>

                    @if(session('error'))
                        <div class="mb-4 font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md border border-red-300 dark:border-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md border border-red-300 dark:border-red-700">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.budgets.store', $property) }}" method="POST">
                        @csrf

                        <div class="mb-6">
                            <x-input-label for="year" :value="__('Pilih Tahun Budget')" />
                            <select id="year" name="year" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                <option value="">-- Pilih Tahun --</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('year')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md p-4 mb-6">
                            <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Informasi:</h4>
                            <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                <li>Sistem akan membuat template budget untuk 12 bulan</li>
                                <li>Semua kategori akun USALI akan diinisialisasi dengan nilai 0</li>
                                <li>Anda dapat mengisi target Occupancy dan ADR untuk setiap bulan</li>
                                <li>Budget dapat diedit kapan saja selama statusnya masih "Draft"</li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-end space-x-2">
                            <x-secondary-button type="button" onclick="window.history.back()">
                                {{ __('Batal') }}
                            </x-secondary-button>
                            <x-primary-button>
                                {{ __('Buat Budget') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
