<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Budget Planning - ') }}{{ $property->name }} ({{ $budgetPeriod->year }})
            </h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
                <x-secondary-button onclick="window.location.href='{{ route('admin.budgets.index', $property) }}'">
                    {{ __('Kembali') }}
                </x-secondary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="budgetPlanner({{ $property->total_rooms }})">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
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

            <!-- Status Badge -->
            <div class="mb-4 flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status Budget:</span>
                    @if($budgetPeriod->status === 'draft')
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            Draft
                        </span>
                    @elseif($budgetPeriod->status === 'approved')
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Approved
                        </span>
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Locked
                        </span>
                    @endif
                </div>

                @if(!$budgetPeriod->isLocked())
                    <div class="flex space-x-2">
                        <form action="{{ route('admin.budgets.updateStatus', [$property, $budgetPeriod]) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="px-3 py-1 text-xs font-semibold rounded-md bg-green-600 text-white hover:bg-green-700">
                                Approve Budget
                            </button>
                        </form>
                        @if($budgetPeriod->isApproved())
                            <form action="{{ route('admin.budgets.updateStatus', [$property, $budgetPeriod]) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="locked">
                                <button type="submit" class="px-3 py-1 text-xs font-semibold rounded-md bg-red-600 text-white hover:bg-red-700" onclick="return confirm('Yakin ingin lock budget? Budget yang terkunci tidak dapat diedit lagi.')">
                                    Lock Budget
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <form action="{{ route('admin.budgets.update', [$property, $budgetPeriod]) }}" method="POST" x-ref="budgetForm">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 overflow-x-auto shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-100 dark:bg-gray-700 z-10" style="min-width: 200px;">
                                    Kategori
                                </th>
                                @for($month = 1; $month <= 12; $month++)
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider" style="min-width: 120px;">
                                        {{ \Carbon\Carbon::create(null, $month)->format('M') }}
                                    </th>
                                @endfor
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-200 dark:bg-gray-600" style="min-width: 120px;">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- SECTION: BUDGET DRIVERS -->
                            <tr class="bg-blue-50 dark:bg-blue-900/20">
                                <td colspan="14" class="px-4 py-2 font-bold text-blue-800 dark:text-blue-300">
                                    BUDGET DRIVERS
                                </td>
                            </tr>

                            <!-- Target Occupancy % -->
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-800">
                                    Target Occupancy %
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    @php
                                        $driver = $drivers->get($month);
                                        $occupancy = $driver?->target_occupancy_pct ?? 0;
                                    @endphp
                                    <td class="px-2 py-2">
                                        <input type="hidden" name="drivers[{{ $month }}][month]" value="{{ $month }}">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               name="drivers[{{ $month }}][target_occupancy_pct]"
                                               x-model="drivers[{{ $month }}].occupancy"
                                               @input="calculateRoomRevenue({{ $month }})"
                                               value="{{ $occupancy }}"
                                               class="w-full px-2 py-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                               {{ $budgetPeriod->isLocked() ? 'readonly' : '' }}>
                                    </td>
                                @endfor
                                <td class="px-4 py-2 text-center bg-gray-50 dark:bg-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                    <span x-text="calculateAverageOccupancy().toFixed(2) + '%'"></span>
                                </td>
                            </tr>

                            <!-- Target ADR -->
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-800">
                                    Target ADR (Rp)
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    @php
                                        $driver = $drivers->get($month);
                                        $adr = $driver?->target_adr ?? 0;
                                    @endphp
                                    <td class="px-2 py-2">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               name="drivers[{{ $month }}][target_adr]"
                                               x-model="drivers[{{ $month }}].adr"
                                               @input="calculateRoomRevenue({{ $month }})"
                                               value="{{ $adr }}"
                                               class="w-full px-2 py-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                               {{ $budgetPeriod->isLocked() ? 'readonly' : '' }}>
                                    </td>
                                @endfor
                                <td class="px-4 py-2 text-center bg-gray-50 dark:bg-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                    <span x-text="formatCurrency(calculateAverageADR())"></span>
                                </td>
                            </tr>

                            <!-- SECTION: REVENUE -->
                            <tr class="bg-green-50 dark:bg-green-900/20">
                                <td colspan="14" class="px-4 py-2 font-bold text-green-800 dark:text-green-300">
                                    REVENUE
                                </td>
                            </tr>

                            @foreach($categories as $groupKey => $categoryGroup)
                                @php
                                    [$department, $type] = explode('|', $groupKey);
                                @endphp

                                @if($type === 'revenue')
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td colspan="14" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">
                                            {{ $department }}
                                        </td>
                                    </tr>

                                    @foreach($categoryGroup as $category)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-800">
                                                {{ $category->name }}
                                            </td>
                                            @for($month = 1; $month <= 12; $month++)
                                                @php
                                                    $plan = $plans->get($category->id)?->get($month);
                                                    $amount = $plan?->amount ?? 0;
                                                    $inputId = "plan_{$category->id}_{$month}";
                                                @endphp
                                                <td class="px-2 py-2">
                                                    <input type="hidden" name="plans[{{ $inputId }}][budget_category_id]" value="{{ $category->id }}">
                                                    <input type="hidden" name="plans[{{ $inputId }}][month]" value="{{ $month }}">
                                                    <input type="number"
                                                           step="0.01"
                                                           min="0"
                                                           name="plans[{{ $inputId }}][amount]"
                                                           x-model="plans['{{ $inputId }}']"
                                                           @input="calculateRowTotal('{{ $category->id }}')"
                                                           value="{{ $amount }}"
                                                           x-ref="category_{{ $category->id }}_month_{{ $month }}"
                                                           class="w-full px-2 py-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                           {{ $budgetPeriod->isLocked() ? 'readonly' : '' }}>
                                                </td>
                                            @endfor
                                            <td class="px-4 py-2 text-right bg-gray-50 dark:bg-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                                <span x-text="formatCurrency(rowTotals['{{ $category->id }}'] || 0)"></span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach

                            <!-- SECTION: EXPENSES -->
                            <tr class="bg-red-50 dark:bg-red-900/20">
                                <td colspan="14" class="px-4 py-2 font-bold text-red-800 dark:text-red-300">
                                    EXPENSES
                                </td>
                            </tr>

                            @foreach($categories as $groupKey => $categoryGroup)
                                @php
                                    [$department, $type] = explode('|', $groupKey);
                                @endphp

                                @if(in_array($type, ['expense_fixed', 'expense_variable', 'payroll']))
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td colspan="14" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">
                                            {{ $department }} - {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </td>
                                    </tr>

                                    @foreach($categoryGroup as $category)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-800">
                                                {{ $category->name }}
                                            </td>
                                            @for($month = 1; $month <= 12; $month++)
                                                @php
                                                    $plan = $plans->get($category->id)?->get($month);
                                                    $amount = $plan?->amount ?? 0;
                                                    $inputId = "plan_{$category->id}_{$month}";
                                                @endphp
                                                <td class="px-2 py-2">
                                                    <input type="hidden" name="plans[{{ $inputId }}][budget_category_id]" value="{{ $category->id }}">
                                                    <input type="hidden" name="plans[{{ $inputId }}][month]" value="{{ $month }}">
                                                    <input type="number"
                                                           step="0.01"
                                                           min="0"
                                                           name="plans[{{ $inputId }}][amount]"
                                                           x-model="plans['{{ $inputId }}']"
                                                           @input="calculateRowTotal('{{ $category->id }}')"
                                                           value="{{ $amount }}"
                                                           class="w-full px-2 py-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                           {{ $budgetPeriod->isLocked() ? 'readonly' : '' }}>
                                                </td>
                                            @endfor
                                            <td class="px-4 py-2 text-right bg-gray-50 dark:bg-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                                <span x-text="formatCurrency(rowTotals['{{ $category->id }}'] || 0)"></span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(!$budgetPeriod->isLocked())
                    <div class="mt-4 flex items-center justify-end space-x-2">
                        <x-secondary-button type="button" onclick="window.location.href='{{ route('admin.budgets.index', $property) }}'">
                            {{ __('Batal') }}
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            {{ __('Simpan Budget') }}
                        </x-primary-button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function budgetPlanner(totalRooms) {
            return {
                totalRooms: totalRooms,
                drivers: Array.from({ length: 13 }, (_, i) => ({ occupancy: 0, adr: 0, days: 30 })),
                plans: {},
                rowTotals: {},

                init() {
                    // Initialize drivers from form
                    for (let month = 1; month <= 12; month++) {
                        const daysInMonth = new Date({{ $budgetPeriod->year }}, month, 0).getDate();
                        this.drivers[month] = {
                            occupancy: parseFloat(this.$el.querySelector(`input[name="drivers[${month}][target_occupancy_pct]"]`)?.value || 0),
                            adr: parseFloat(this.$el.querySelector(`input[name="drivers[${month}][target_adr]"]`)?.value || 0),
                            days: daysInMonth
                        };
                    }

                    // Initialize plans from form
                    const planInputs = this.$el.querySelectorAll('input[name^="plans["][name$="][amount]"]');
                    planInputs.forEach(input => {
                        const key = input.name.match(/plans\[(.*?)\]\[amount\]/)[1];
                        this.plans[key] = parseFloat(input.value || 0);
                    });

                    // Calculate initial totals
                    this.calculateAllTotals();
                },

                calculateRoomRevenue(month) {
                    const occupancy = this.drivers[month].occupancy / 100;
                    const adr = this.drivers[month].adr;
                    const days = this.drivers[month].days;

                    const roomsSold = this.totalRooms * days * occupancy;
                    const roomRevenue = roomsSold * adr;

                    // Auto-fill Room Revenue (assuming category 4010 is first room revenue)
                    const roomRevenueInput = this.$refs[`category_${this.getRoomRevenueCategoryId()}_month_${month}`];
                    if (roomRevenueInput) {
                        const key = `plan_${this.getRoomRevenueCategoryId()}_${month}`;
                        this.plans[key] = Math.round(roomRevenue);
                        this.calculateRowTotal(this.getRoomRevenueCategoryId());
                    }
                },

                getRoomRevenueCategoryId() {
                    // Find the first category ID that's a room revenue (you might need to adjust this)
                    const firstRoomRevenueInput = this.$el.querySelector('input[name^="plans[plan_"][name*="_1]"][name$="[amount]"]');
                    if (firstRoomRevenueInput) {
                        const match = firstRoomRevenueInput.name.match(/plans\[plan_(\d+)_/);
                        if (match) return match[1];
                    }
                    return null;
                },

                calculateRowTotal(categoryId) {
                    let total = 0;
                    for (let month = 1; month <= 12; month++) {
                        const key = `plan_${categoryId}_${month}`;
                        total += parseFloat(this.plans[key] || 0);
                    }
                    this.rowTotals[categoryId] = total;
                },

                calculateAllTotals() {
                    Object.keys(this.plans).forEach(key => {
                        const categoryId = key.split('_')[1];
                        this.calculateRowTotal(categoryId);
                    });
                },

                calculateAverageOccupancy() {
                    let sum = 0;
                    for (let month = 1; month <= 12; month++) {
                        sum += parseFloat(this.drivers[month].occupancy || 0);
                    }
                    return sum / 12;
                },

                calculateAverageADR() {
                    let sum = 0;
                    for (let month = 1; month <= 12; month++) {
                        sum += parseFloat(this.drivers[month].adr || 0);
                    }
                    return sum / 12;
                },

                formatCurrency(value) {
                    return 'Rp ' + Math.round(value).toLocaleString('id-ID');
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
