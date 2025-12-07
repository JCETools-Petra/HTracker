<?php

namespace App\Exports;

use App\Models\FinancialCategory;
use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BudgetTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    protected $propertyId;
    protected $year;
    protected $property;
    protected $departmentColors = [
        'Front Office' => 'E7E6F7',
        'Housekeeping' => 'FCE4D6',
        'F&B Product (Kitchen)' => 'E2F0D9',
        'F&B Service' => 'DEEBF7',
        'POMAC (Property Operation, Maintenance & Energy Cost)' => 'FFF2CC',
        'Accounting & General' => 'F4B084',
        'Sales & Marketing (MICE)' => 'C5E0B4',
    ];

    public function __construct(int $propertyId, int $year)
    {
        $this->propertyId = $propertyId;
        $this->year = $year;
        $this->property = Property::find($propertyId);
    }

    public function collection()
    {
        // Get all departments (top-level categories)
        $departments = FinancialCategory::forProperty($this->propertyId)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $rows = [];

        foreach ($departments as $department) {
            // Add department header row
            $rows[] = [
                'category_id' => '',
                'department' => strtoupper($department->name),
                'category_name' => '',
                'level' => 0,
                'is_header' => true,
                'jan' => '', 'feb' => '', 'mar' => '', 'apr' => '', 'may' => '', 'jun' => '',
                'jul' => '', 'aug' => '', 'sep' => '', 'oct' => '', 'nov' => '', 'dec' => '',
            ];

            // Get all expense categories under this department
            $this->addCategoriesRecursive($rows, $department, 1);

            // Add empty row between departments
            $rows[] = [
                'category_id' => '',
                'department' => '',
                'category_name' => '',
                'level' => 0,
                'is_header' => false,
                'jan' => '', 'feb' => '', 'mar' => '', 'apr' => '', 'may' => '', 'jun' => '',
                'jul' => '', 'aug' => '', 'sep' => '', 'oct' => '', 'nov' => '', 'dec' => '',
            ];
        }

        return collect($rows);
    }

    private function addCategoriesRecursive(&$rows, $parent, $level)
    {
        $children = FinancialCategory::forProperty($this->propertyId)
            ->where('parent_id', $parent->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($children as $category) {
            // Only add expense type leaf nodes (input-eligible categories)
            if ($category->type === 'expense' && !$category->children()->exists()) {
                $indent = str_repeat('  ', $level); // 2 spaces per level

                $rows[] = [
                    'category_id' => $category->id,
                    'department' => $this->getDepartmentName($category),
                    'category_name' => $indent . $category->name,
                    'level' => $level,
                    'is_header' => false,
                    'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0, 'may' => 0, 'jun' => 0,
                    'jul' => 0, 'aug' => 0, 'sep' => 0, 'oct' => 0, 'nov' => 0, 'dec' => 0,
                ];
            } else {
                // For parent categories, add as section header
                if ($category->children()->exists()) {
                    $indent = str_repeat('  ', $level);
                    $rows[] = [
                        'category_id' => '',
                        'department' => '',
                        'category_name' => $indent . '▶ ' . strtoupper($category->name),
                        'level' => $level,
                        'is_header' => true,
                        'jan' => '', 'feb' => '', 'mar' => '', 'apr' => '', 'may' => '', 'jun' => '',
                        'jul' => '', 'aug' => '', 'sep' => '', 'oct' => '', 'nov' => '', 'dec' => '',
                    ];
                }

                // Recurse to children
                $this->addCategoriesRecursive($rows, $category, $level + 1);
            }
        }
    }

    public function headings(): array
    {
        return [
            [
                'BUDGET TEMPLATE - ' . ($this->property ? strtoupper($this->property->name) : 'PROPERTY'),
            ],
            [
                'Tahun: ' . $this->year,
            ],
            [], // Empty row
            [
                'Category ID',
                'Department',
                'Category Name',
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
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title row
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Year info row
        $sheet->mergeCells('A2:O2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Header row styling
        $sheet->getStyle('A4:O4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(25);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Category ID
            'B' => 35,  // Department
            'C' => 50,  // Category Name
            'D' => 12,  // January
            'E' => 12,  // February
            'F' => 12,  // March
            'G' => 12,  // April
            'H' => 12,  // May
            'I' => 12,  // June
            'J' => 12,  // July
            'K' => 12,  // August
            'L' => 12,  // September
            'M' => 12,  // October
            'N' => 12,  // November
            'O' => 12,  // December
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $currentDepartment = '';
                $departmentColor = 'FFFFFF';

                // Start from row 5 (after headers)
                for ($row = 5; $row <= $highestRow; $row++) {
                    $departmentCell = $sheet->getCell("B{$row}")->getValue();
                    $categoryCell = $sheet->getCell("C{$row}")->getValue();
                    $categoryId = $sheet->getCell("A{$row}")->getValue();

                    // Check if this is a department header row
                    if (!empty($departmentCell) && $departmentCell === strtoupper($departmentCell) && empty($categoryId)) {
                        $currentDepartment = $departmentCell;
                        $departmentColor = $this->getDepartmentColor($departmentCell);

                        // Style department header
                        $sheet->mergeCells("B{$row}:O{$row}");
                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                                'color' => ['rgb' => '000000'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $departmentColor],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                            'borders' => [
                                'outline' => [
                                    'borderStyle' => Border::BORDER_MEDIUM,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                    }
                    // Check if this is a section header (starts with ▶)
                    elseif (!empty($categoryCell) && strpos($categoryCell, '▶') !== false) {
                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'italic' => true,
                                'size' => 10,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $this->adjustColorBrightness($departmentColor, 30)],
                            ],
                            'borders' => [
                                'bottom' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                        ]);
                    }
                    // Regular category rows
                    elseif (!empty($categoryId)) {
                        // Apply light background
                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFFFF'],
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => 'D0D0D0'],
                                ],
                            ],
                        ]);

                        // Number format for month columns
                        $sheet->getStyle("D{$row}:O{$row}")->getNumberFormat()
                            ->setFormatCode('#,##0');

                        // Center align month values
                        $sheet->getStyle("D{$row}:O{$row}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // Freeze panes at row 5 (after header)
                $sheet->freezePane('A5');

                // Auto-filter on header row
                $sheet->setAutoFilter('A4:O4');
            },
        ];
    }

    public function title(): string
    {
        return 'Budget ' . $this->year;
    }

    private function getDepartmentName(FinancialCategory $category): string
    {
        $root = $category;
        while ($root->parent) {
            $root = $root->parent;
        }
        return $root->name;
    }

    private function getDepartmentColor(string $departmentName): string
    {
        foreach ($this->departmentColors as $key => $color) {
            if (stripos($departmentName, $key) !== false || stripos($key, $departmentName) !== false) {
                return $color;
            }
        }
        return 'E0E0E0'; // Default gray
    }

    private function adjustColorBrightness(string $hexColor, int $percent): string
    {
        $hex = str_replace('#', '', $hexColor);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = min(255, $r + (255 - $r) * ($percent / 100));
        $g = min(255, $g + (255 - $g) * ($percent / 100));
        $b = min(255, $b + (255 - $b) * ($percent / 100));

        return sprintf('%02X%02X%02X', $r, $g, $b);
    }
}
