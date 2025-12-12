{{-- Recursive Category Row Component --}}
@php
    $isBold = $category['has_children'] || $category['is_payroll'];
    
    // Background Color Logic
    $bgClass = '';
    if ($category['level'] === 0) {
        $bgClass = 'bg-gray-100 dark:bg-gray-700'; // Header Utama (Abu-abu)
    } elseif ($category['is_payroll']) {
        $bgClass = 'bg-yellow-50 dark:bg-yellow-900'; // Payroll (Kuning Tipis)
    } elseif ($category['has_children']) {
        $bgClass = 'bg-gray-50 dark:bg-gray-800'; // Sub-Header
    }

    // Variance Color Logic (PENTING: Membedakan Revenue vs Expense)
    // Expense: Actual > Budget (Positif) = MERAH (Boros)
    // Revenue: Actual > Budget (Positif) = HIJAU (Untung)
    
    $varianceCurrentColor = 'text-gray-500';
    if ($category['variance_current'] != 0) {
        if (($category['type'] ?? 'expense') === 'expense') {
            $varianceCurrentColor = $category['variance_current'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
        } else {
            $varianceCurrentColor = $category['variance_current'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
        }
    }

    $varianceYtdColor = 'text-gray-500';
    if ($category['variance_ytd'] != 0) {
        if (($category['type'] ?? 'expense') === 'expense') {
            $varianceYtdColor = $category['variance_ytd'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
        } else {
            $varianceYtdColor = $category['variance_ytd'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
        }
    }
@endphp

<tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $bgClass }}">
    {{-- NAME COLUMN --}}
    <td class="px-6 py-2 text-sm {{ $isBold ? 'font-bold' : '' }} text-gray-900 dark:text-gray-100" 
        style="padding-left: {{ ($category['level'] * 1.5 + 1.5) }}rem">
        <div class="flex items-center">
            @if($category['has_children'])
                <span class="mr-1 text-gray-400 text-xs">▼</span>
            @endif
            
            {{ $category['name'] }}

            @if($category['code'])
                <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 border border-blue-200">Auto</span>
            @endif
            @if($category['is_payroll'])
                <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 border border-yellow-200">Payroll</span>
            @endif
        </div>
    </td>

    {{-- CURRENT MONTH --}}
    <td class="px-6 py-2 text-right text-sm {{ $isBold ? 'font-semibold' : '' }} text-gray-900 dark:text-gray-100">
        @if($category['actual_current'] != 0 || !$category['has_children'])
            Rp {{ number_format($category['actual_current'], 0, ',', '.') }}
        @endif
    </td>
    <td class="px-6 py-2 text-right text-sm {{ $isBold ? 'font-semibold' : '' }} text-gray-500 dark:text-gray-400">
        @if($category['budget_current'] != 0 || !$category['has_children'])
            Rp {{ number_format($category['budget_current'], 0, ',', '.') }}
        @endif
    </td>
    <td class="px-6 py-2 text-right text-sm {{ $isBold ? 'font-bold' : 'font-medium' }} border-r border-gray-200 dark:border-gray-700 {{ $varianceCurrentColor }}">
        @if($category['variance_current'] != 0 || !$category['has_children'])
            Rp {{ number_format($category['variance_current'], 0, ',', '.') }}
        @endif
    </td>

    {{-- YTD --}}
    <td class="px-6 py-2 text-right text-sm {{ $isBold ? 'font-semibold' : '' }} text-gray-900 dark:text-gray-100">
        @if($category['actual_ytd'] != 0 || !$category['has_children'])
            Rp {{ number_format($category['actual_ytd'], 0, ',', '.') }}
        @endif
    </td>
    <td class="px-6 py-2 text-right text-sm {{ $isBold ? 'font-semibold' : '' }} text-gray-500 dark:text-gray-400">
        @if($category['budget_ytd'] != 0 || !$category['has_children'])
            Rp {{ number_format($category['budget_ytd'], 0, ',', '.') }}
        @endif
    </td>
    <td class="px-6 py-2 text-right text-sm {{ $isBold ? 'font-bold' : 'font-medium' }} {{ $varianceYtdColor }}">
        @if($category['variance_ytd'] != 0 || !$category['has_children'])
            Rp {{ number_format($category['variance_ytd'], 0, ',', '.') }}
        @endif
    </td>
</tr>

{{-- Recursively render children --}}
@if($category['has_children'] && count($category['children']) > 0)
    @foreach($category['children'] as $child)
        @include('financial.partials.category-row', ['category' => $child])
    @endforeach
@endif