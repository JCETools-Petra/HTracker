<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Input Expense - ') }}{{ $property->name }} ({{ $budgetPeriod->year }})
            </h2>
            <x-secondary-button onclick="window.location.href='{{ route('admin.budgets.show', [$property, $budgetPeriod]) }}'">
                {{ __('Kembali ke Dashboard') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Form Input Expense</h3>

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

                    <form action="{{ route('admin.budgets.expenses.store', [$property, $budgetPeriod]) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          x-data="expenseForm()">
                        @csrf

                        <!-- Department Selection -->
                        <div class="mb-6">
                            <x-input-label for="budget_department_id" :value="__('Department')" />
                            <select id="budget_department_id"
                                    name="budget_department_id"
                                    x-model="selectedDepartment"
                                    @change="updateDepartmentInfo()"
                                    class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                    required>
                                <option value="">-- Pilih Department --</option>
                                @foreach($budgetPeriod->departments as $dept)
                                    <option value="{{ $dept->id }}"
                                            data-allocated="{{ $dept->allocated_budget }}"
                                            data-used="{{ $dept->expenses->sum('amount') }}"
                                            {{ old('budget_department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('budget_department_id')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <!-- Department Budget Info -->
                            <div x-show="selectedDepartment" class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Allocated:</span>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            Rp <span x-text="formatNumber(deptAllocated)"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Used:</span>
                                        <p class="font-semibold text-orange-600">
                                            Rp <span x-text="formatNumber(deptUsed)"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Remaining:</span>
                                        <p class="font-semibold text-green-600">
                                            Rp <span x-text="formatNumber(deptRemaining)"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full"
                                             :class="deptPercentage < 60 ? 'bg-green-600' : (deptPercentage < 85 ? 'bg-yellow-600' : 'bg-red-600')"
                                             :style="'width: ' + Math.min(deptPercentage, 100) + '%'"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <span x-text="deptPercentage.toFixed(1)"></span>% terpakai
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Expense Date -->
                        <div class="mb-6">
                            <x-input-label for="expense_date" :value="__('Tanggal Expense')" />
                            <input type="date"
                                   id="expense_date"
                                   name="expense_date"
                                   class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                   required
                                   max="{{ date('Y-m-d') }}"
                                   value="{{ old('expense_date', date('Y-m-d')) }}">
                            @error('expense_date')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <x-input-label for="description" :value="__('Deskripsi')" />
                            <input type="text"
                                   id="description"
                                   name="description"
                                   class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                   required
                                   value="{{ old('description') }}"
                                   placeholder="Contoh: Pembelian Guest Amenities">
                            <p class="mt-1 text-xs text-gray-500">Jelaskan pengeluaran dengan jelas dan spesifik</p>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Amount -->
                        <div class="mb-6">
                            <x-input-label for="amount" :value="__('Jumlah (Rp)')" />
                            <input type="number"
                                   id="amount"
                                   name="amount"
                                   x-model="expenseAmount"
                                   @input="checkBudgetLimit()"
                                   class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                   required
                                   min="1"
                                   step="1"
                                   value="{{ old('amount') }}"
                                   placeholder="5000000">
                            <p class="mt-1 text-xs text-gray-500">Masukkan nominal tanpa titik atau koma</p>

                            <!-- Budget Warning -->
                            <div x-show="budgetWarning" class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md">
                                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                                    ⚠️ Expense ini akan membuat budget department melebihi alokasi!
                                </p>
                            </div>

                            @error('amount')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div class="mb-6">
                            <x-input-label for="category" :value="__('Kategori')" />
                            <select id="category"
                                    name="category"
                                    class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                <option value="">-- Pilih Kategori (Optional) --</option>
                                <option value="Supplies" {{ old('category') == 'Supplies' ? 'selected' : '' }}>Supplies</option>
                                <option value="Payroll" {{ old('category') == 'Payroll' ? 'selected' : '' }}>Payroll</option>
                                <option value="Utilities" {{ old('category') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                                <option value="Marketing" {{ old('category') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                                <option value="Maintenance" {{ old('category') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="Training" {{ old('category') == 'Training' ? 'selected' : '' }}>Training</option>
                                <option value="Equipment" {{ old('category') == 'Equipment' ? 'selected' : '' }}>Equipment</option>
                                <option value="Miscellaneous" {{ old('category') == 'Miscellaneous' ? 'selected' : '' }}>Miscellaneous</option>
                            </select>
                            @error('category')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Receipt Number -->
                        <div class="mb-6">
                            <x-input-label for="receipt_number" :value="__('Nomor Receipt/Invoice (Optional)')" />
                            <input type="text"
                                   id="receipt_number"
                                   name="receipt_number"
                                   class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                   value="{{ old('receipt_number') }}"
                                   placeholder="INV-2026-001">
                            @error('receipt_number')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Receipt File Upload -->
                        <div class="mb-6">
                            <x-input-label for="receipt_file" :value="__('Upload Receipt/Bukti Pembayaran (Optional)')" />
                            <input type="file"
                                   id="receipt_file"
                                   name="receipt_file"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="block mt-1 w-full text-sm text-gray-900 dark:text-gray-300
                                          border border-gray-300 dark:border-gray-700 rounded-md cursor-pointer
                                          bg-gray-50 dark:bg-gray-900 focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">Format yang didukung: PDF, JPG, PNG (Max 2MB)</p>
                            @error('receipt_file')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <x-input-label for="notes" :value="__('Catatan (Optional)')" />
                            <textarea id="notes"
                                      name="notes"
                                      rows="3"
                                      class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                      placeholder="Catatan tambahan tentang pengeluaran ini...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end space-x-2">
                            <x-secondary-button type="button" onclick="window.location.href='{{ route('admin.budgets.show', [$property, $budgetPeriod]) }}'">
                                {{ __('Batal') }}
                            </x-secondary-button>
                            <x-primary-button>
                                {{ __('Simpan Expense') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 overflow-hidden shadow-sm sm:rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-3">💡 Tips Input Expense</h4>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                    <li>• Input expense sesegera mungkin setelah transaksi terjadi</li>
                    <li>• Upload receipt untuk dokumentasi dan audit trail</li>
                    <li>• Gunakan deskripsi yang jelas dan spesifik</li>
                    <li>• Pastikan kategori sesuai dengan jenis pengeluaran</li>
                    <li>• Budget akan otomatis berkurang setelah expense disimpan</li>
                </ul>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function expenseForm() {
            return {
                selectedDepartment: '{{ old('budget_department_id') }}',
                deptAllocated: 0,
                deptUsed: 0,
                deptRemaining: 0,
                deptPercentage: 0,
                expenseAmount: {{ old('amount', 0) }},
                budgetWarning: false,

                init() {
                    if (this.selectedDepartment) {
                        this.updateDepartmentInfo();
                    }
                },

                updateDepartmentInfo() {
                    const select = document.getElementById('budget_department_id');
                    const option = select.options[select.selectedIndex];

                    if (option.value) {
                        this.deptAllocated = parseFloat(option.dataset.allocated) || 0;
                        this.deptUsed = parseFloat(option.dataset.used) || 0;
                        this.deptRemaining = this.deptAllocated - this.deptUsed;
                        this.deptPercentage = this.deptAllocated > 0 ? (this.deptUsed / this.deptAllocated) * 100 : 0;
                        this.checkBudgetLimit();
                    }
                },

                checkBudgetLimit() {
                    const amount = parseFloat(this.expenseAmount) || 0;
                    this.budgetWarning = (this.deptUsed + amount) > this.deptAllocated;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
