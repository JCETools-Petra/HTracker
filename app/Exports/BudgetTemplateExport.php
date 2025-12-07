<?php

namespace App\Exports;

use App\Models\FinancialCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class BudgetTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $propertyId;
    protected $year;

    public function __construct(int $propertyId, int $year)
    {
        $this->propertyId = $propertyId;
        $this->year = $year;
    }

    public function collection()
    {
        // Get all input-eligible categories (leaf nodes only, excluding calculated types)
        $categories = FinancialCategory::forProperty($this->propertyId)
            ->where('type', 'expense')
            ->whereDoesntHave('children') // Only leaf nodes
            ->orderBy('sort_order')
            ->get();

        $rows = [];
        foreach ($categories as $category) {
            $rows[] = [
                'category_id' => $category->id,
                'department' => $this->getDepartmentName($category),
                'category_path' => $this->getCategoryPath($category),
                'january' => 0,
                'february' => 0,
                'march' => 0,
                'april' => 0,
                'may' => 0,
                'june' => 0,
                'july' => 0,
                'august' => 0,
                'september' => 0,
                'october' => 0,
                'november' => 0,
                'december' => 0,
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Category ID',
            'Department',
            'Category',
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Category ID
            'B' => 30,  // Department
            'C' => 50,  // Category Path
            'D' => 15,  // January
            'E' => 15,  // February
            'F' => 15,  // March
            'G' => 15,  // April
            'H' => 15,  // May
            'I' => 15,  // June
            'J' => 15,  // July
            'K' => 15,  // August
            'L' => 15,  // September
            'M' => 15,  // October
            'N' => 15,  // November
            'O' => 15,  // December
        ];
    }

    private function getDepartmentName(FinancialCategory $category): string
    {
        $root = $category;
        while ($root->parent) {
            $root = $root->parent;
        }
        return $root->name;
    }

    private function getCategoryPath(FinancialCategory $category): string
    {
        $path = [];
        $current = $category;

        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }

        return implode(' > ', $path);
    }
}
