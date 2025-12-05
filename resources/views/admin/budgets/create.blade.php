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

    <div class="py-12" x-data="budgetForm()">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Setup Budget Tahunan</h3>

                    @if(session('error'))
                        <div class="mb-4 font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 font-medium text-sm text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 p-3 rounded-md">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.budgets.store', $property) }}" method="POST">
                        @csrf

                        <!-- Step 1: Basic Info -->
                        <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-4">Step 1: Informasi Dasar</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="year" :value="__('Tahun Budget')" />
                                    <select id="year" name="year" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
                                        <option value="">-- Pilih Tahun --</option>
                                        @foreach($availableYears as $year)
                                            <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    @error('year')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Financial Targets -->
                        <div class="mb-8 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h4 class="font-semibold text-green-800 dark:text-green-300 mb-4">Step 2: Target Finansial</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="total_revenue_target" :value="__('Target Revenue Tahunan (Rp)')" />
                                    <input type="number"
                                           id="total_revenue_target"
                                           name="total_revenue_target"
                                           x-model="revenueTarget"
                                           @input="calculateProfit()"
                                           class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                           required
                                           min="0"
                                           step="1000"
                                           value="{{ old('total_revenue_target', 0) }}">
                                    <p class="mt-1 text-xs text-gray-500">Contoh: 1200000000 (1.2 Miliar)</p>
                                    @error('total_revenue_target')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input-label for="total_expense_budget" :value="__('Budget Expense Tahunan (Rp)')" />
                                    <input type="number"
                                           id="total_expense_budget"
                                           name="total_expense_budget"
                                           x-model="expenseBudget"
                                           @input="calculateProfit()"
                                           class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                           required
                                           min="0"
                                           step="1000"
                                           value="{{ old('total_expense_budget', 0) }}">
                                    <p class="mt-1 text-xs text-gray-500">Contoh: 800000000 (800 Juta)</p>
                                    @error('total_expense_budget')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Target Profit:</span>
                                    <span class="text-2xl font-bold" :class="targetProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        Rp <span x-text="formatNumber(targetProfit)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Department Allocations -->
                        <div class="mb-8 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-semibold text-purple-800 dark:text-purple-300">Step 3: Alokasi Budget per Department</h4>
                                <button type="button"
                                        @click="addDepartment()"
                                        class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-500">
                                    + Tambah Department
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(dept, index) in departments" :key="index">
                                    <div class="flex gap-3 items-start p-3 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Department</label>
                                                <input type="text"
                                                       x-model="dept.name"
                                                       :name="'departments[' + index + '][name]'"
                                                       class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                                       required
                                                       placeholder="Contoh: Rooms Department">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode</label>
                                                <input type="text"
                                                       x-model="dept.code"
                                                       :name="'departments[' + index + '][code]'"
                                                       class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                                       required
                                                       placeholder="RMS"
                                                       maxlength="10">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Budget Allocation (Rp)</label>
                                                <input type="number"
                                                       x-model="dept.budget"
                                                       @input="calculateTotalAllocation()"
                                                       :name="'departments[' + index + '][allocated_budget]'"
                                                       class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                                       required
                                                       min="0"
                                                       step="1000">
                                            </div>
                                        </div>
                                        <button type="button"
                                                @click="removeDepartment(index)"
                                                class="mt-6 px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-500"
                                                x-show="departments.length > 1">
                                            Hapus
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Total Alokasi:</span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                        Rp <span x-text="formatNumber(totalAllocation)"></span>
                                    </span>
                                </div>
                                <div class="mt-2 flex justify-between items-center text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Expense Budget:</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        Rp <span x-text="formatNumber(expenseBudget)"></span>
                                    </span>
                                </div>
                                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Selisih:</span>
                                    <span class="text-lg font-bold" :class="allocationDiff == 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <span x-text="allocationDiff >= 0 ? '+' : ''"></span>Rp <span x-text="formatNumber(Math.abs(allocationDiff))"></span>
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-gray-500" x-show="allocationDiff != 0">
                                    <template x-if="allocationDiff > 0">
                                        <span class="text-red-600">⚠️ Total alokasi melebihi expense budget sebesar Rp <span x-text="formatNumber(allocationDiff)"></span></span>
                                    </template>
                                    <template x-if="allocationDiff < 0">
                                        <span class="text-yellow-600">ℹ️ Masih ada budget Rp <span x-text="formatNumber(Math.abs(allocationDiff))"></span> yang belum dialokasikan</span>
                                    </template>
                                </p>
                            </div>
                        </div>

                        <!-- Submit Button -->
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

            <!-- Quick Setup Templates -->
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">💡 Template Cepat</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Gunakan template untuk mengisi alokasi dengan cepat:</p>
                    <div class="flex gap-2">
                        <button type="button"
                                @click="loadTemplate('standard')"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Template Standard Hotel
                        </button>
                        <button type="button"
                                @click="loadTemplate('boutique')"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Template Boutique Hotel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function budgetForm() {
            return {
                revenueTarget: {{ old('total_revenue_target', 0) }},
                expenseBudget: {{ old('total_expense_budget', 0) }},
                targetProfit: 0,
                totalAllocation: 0,
                allocationDiff: 0,
                departments: [
                    { name: 'Rooms Department', code: 'RMS', budget: 0 },
                    { name: 'F&B Department', code: 'FNB', budget: 0 },
                    { name: 'Marketing', code: 'MKT', budget: 0 },
                    { name: 'Maintenance', code: 'MNT', budget: 0 },
                    { name: 'Admin & General', code: 'ADM', budget: 0 }
                ],

                init() {
                    this.calculateProfit();
                    this.calculateTotalAllocation();
                },

                calculateProfit() {
                    this.targetProfit = this.revenueTarget - this.expenseBudget;
                    this.calculateTotalAllocation();
                },

                calculateTotalAllocation() {
                    this.totalAllocation = this.departments.reduce((sum, dept) => sum + parseFloat(dept.budget || 0), 0);
                    this.allocationDiff = this.totalAllocation - this.expenseBudget;
                },

                addDepartment() {
                    this.departments.push({ name: '', code: '', budget: 0 });
                },

                removeDepartment(index) {
                    this.departments.splice(index, 1);
                    this.calculateTotalAllocation();
                },

                loadTemplate(type) {
                    const budget = this.expenseBudget;

                    if (type === 'standard') {
                        // Standard Hotel: 30% Rooms, 35% F&B, 15% Marketing, 15% Maintenance, 5% Admin
                        this.departments = [
                            { name: 'Rooms Department', code: 'RMS', budget: Math.round(budget * 0.30) },
                            { name: 'F&B Department', code: 'FNB', budget: Math.round(budget * 0.35) },
                            { name: 'Marketing', code: 'MKT', budget: Math.round(budget * 0.15) },
                            { name: 'Maintenance', code: 'MNT', budget: Math.round(budget * 0.15) },
                            { name: 'Admin & General', code: 'ADM', budget: Math.round(budget * 0.05) }
                        ];
                    } else if (type === 'boutique') {
                        // Boutique Hotel: 25% Rooms, 30% F&B, 20% Marketing, 15% Maintenance, 10% Admin
                        this.departments = [
                            { name: 'Rooms Department', code: 'RMS', budget: Math.round(budget * 0.25) },
                            { name: 'F&B Department', code: 'FNB', budget: Math.round(budget * 0.30) },
                            { name: 'Marketing', code: 'MKT', budget: Math.round(budget * 0.20) },
                            { name: 'Maintenance', code: 'MNT', budget: Math.round(budget * 0.15) },
                            { name: 'Admin & General', code: 'ADM', budget: Math.round(budget * 0.10) }
                        ];
                    }

                    this.calculateTotalAllocation();
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
