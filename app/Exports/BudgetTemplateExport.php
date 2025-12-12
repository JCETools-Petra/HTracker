<?php

namespace App\Exports;

use App\Models\FinancialCategory;
use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BudgetTemplateExport implements FromArray, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    protected $propertyId;
    protected $year;
    protected $property;

    protected $departmentColors = [
        // --- Revenue Colors ---
        'Room Revenue' => 'C6EFCE', // Hijau
        'F&B Revenue' => 'FFEB9C',  // Kuning
        'MICE Revenue' => 'FFC7CE', // Merah Muda
        'Other Revenue' => 'E2EFDA',
        'Beverage Revenue' => 'C6EFCE',
        'Breakfast Revenue' => 'FFEB9C',
        'Breakfast Revenue/Incl. Bell & Ermasu' => 'FFEB9C',
        'Lunch Revenue' => 'FFC7CE',
        'Dinner Revenue' => 'E2EFDA',
        'Package Revenue' => 'C6EFCE',
        'Rental Area' => 'FFEB9C',
        'OTHERS' => 'FFC7CE',

        // --- Expense Colors ---
        'Front Office' => 'E7E6F7',
        'Housekeeping' => 'FCE4D6',
        'F&B Product (Kitchen)' => 'E2F0D9',
        'F&B Service' => 'DEEBF7',
        'POMAC' => 'FFF2CC',
        'Accounting & General' => 'F4B084',
        'Sales & Marketing' => 'C5E0B4',
    ];

    public function __construct(int $propertyId, int $year)
    {
        $this->propertyId = $propertyId;
        $this->year = $year;
        $this->property = Property::find($propertyId);
    }

    public function array(): array
    {
        $propertyName = $this->property ? strtoupper($this->property->name) : 'PROPERTY';

        $data = [
            ['BUDGET TEMPLATE - ' . $propertyName, '', '', '', '', '', '', '', '', '', '', '', '', '', ''], 
            ['Tahun: ' . $this->year, '', '', '', '', '', '', '', '', '', '', '', '', '', ''], 
            [''], 
            ['Category ID', 'Department', 'Category Name', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        ];

        // LOGIKA UTAMA: Ambil kategori Root (TAPI JANGAN AMBIL YANG 'calculated')
        $rootCategories = FinancialCategory::forProperty($this->propertyId)
            ->whereNull('parent_id')
            ->where('type', '!=', 'calculated') // <--- FILTER PENTING: Hapus baris total/kalkulasi
            ->orderBy('sort_order')
            ->get();

        foreach ($rootCategories as $rootCategory) {
            $hasChildren = $rootCategory->children()->where('type', '!=', 'calculated')->exists();

            if ($hasChildren) {
                // Header Departemen
                $data[] = [
                    '', 
                    strtoupper($rootCategory->name), 
                    '', 
                    '', '', '', '', '', '', '', '', '', '', '', '' 
                ];
                $this->addCategoriesRecursive($data, $rootCategory, 1);
            } else {
                // Baris Input Langsung
                $data[] = [
                    $rootCategory->id, 
                    strtoupper($rootCategory->name), 
                    $rootCategory->name,             
                    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 
                ];
            }
            $data[] = ['']; 
        }

        return $data;
    }

    private function addCategoriesRecursive(&$data, $parent, $level)
    {
        // Ambil anak-anaknya, TAPI filter yang 'calculated' agar TOTAL tidak muncul
        $children = FinancialCategory::forProperty($this->propertyId)
            ->where('parent_id', $parent->id)
            ->where('type', '!=', 'calculated') // <--- FILTER PENTING
            ->orderBy('sort_order')
            ->get();

        foreach ($children as $category) {
            $hasChildren = $category->children()->where('type', '!=', 'calculated')->exists();

            if ($hasChildren) {
                $indent = str_repeat('  ', $level);
                $data[] = [
                    '', '', $indent . '▶ ' . strtoupper($category->name), 
                    '', '', '', '', '', '', '', '', '', '', '', '' 
                ];
                $this->addCategoriesRecursive($data, $category, $level + 1);
            } else {
                $indent = str_repeat('  ', $level);
                $data[] = [
                    $category->id, 
                    $this->getDepartmentName($category), 
                    $indent . $category->name, 
                    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 
                ];
            }
        }
    }

    // --- STYLING (Sama seperti sebelumnya) ---
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->mergeCells('A2:O2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $sheet->getStyle('A4:O4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(25);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 35, 'C' => 50,
            'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12, 'H' => 12, 'I' => 12,
            'J' => 12, 'K' => 12, 'L' => 12, 'M' => 12, 'N' => 12, 'O' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 5; $row <= $highestRow; $row++) {
                    $categoryId = $sheet->getCell("A{$row}")->getValue();
                    $departmentCell = $sheet->getCell("B{$row}")->getValue();
                    $categoryCell = $sheet->getCell("C{$row}")->getValue();

                    if (!empty($departmentCell) && empty($categoryId) && empty($categoryCell)) {
                        $departmentColor = $this->getDepartmentColor($departmentCell);
                        if ($departmentCell === 'REVENUE SECTIONS') { $departmentColor = '808080'; }

                        $sheet->mergeCells("B{$row}:O{$row}");
                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $departmentColor]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                    }
                    elseif (!empty($categoryCell) && strpos($categoryCell, '▶') !== false) {
                        $deptName = $this->getRootCategoryName($row, $sheet);
                        $color = $this->getDepartmentColor($deptName);
                        
                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'italic' => true, 'size' => 10],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->adjustColorBrightness($color, 30)]],
                            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '808080']]],
                        ]);
                    }
                    elseif (!empty($categoryId)) {
                        $deptName = $sheet->getCell("B{$row}")->getValue();
                        $rowColor = 'FFFFFF';
                        
                        if ($this->isRevenueCategory($deptName)) {
                             $rowColor = $this->getDepartmentColor($deptName);
                             $rowColor = $this->adjustColorBrightness($rowColor, 40); 
                        }

                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowColor]],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']]],
                        ]);
                        $sheet->getStyle("D{$row}:O{$row}")->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle("D{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                $sheet->freezePane('A5');
                $sheet->setAutoFilter('A4:O4');
            },
        ];
    }

    public function title(): string { return 'Budget ' . $this->year; }

    private function getDepartmentName(FinancialCategory $category): string
    {
        $root = $category;
        while ($root->parent) { $root = $root->parent; }
        return $root->name;
    }

    private function getRootCategoryName($currentRow, $sheet): string {
        for ($i = $currentRow; $i >= 5; $i--) {
             $dept = $sheet->getCell("B{$i}")->getValue();
             if (!empty($dept) && empty($sheet->getCell("A{$i}")->getValue()) && empty($sheet->getCell("C{$i}")->getValue())) { return $dept; }
        }
        return '';
    }

    private function isRevenueCategory($name): bool {
        $revenueKeys = ['Room Revenue', 'F&B Revenue', 'MICE Revenue', 'Other Revenue', 'Beverage', 'Breakfast', 'Lunch', 'Dinner', 'Package', 'Rental', 'OTHERS'];
        foreach ($revenueKeys as $key) { if (stripos($name, $key) !== false) return true; }
        return false;
    }

    private function getDepartmentColor(string $departmentName): string
    {
        foreach ($this->departmentColors as $key => $color) {
            if (stripos($departmentName, $key) !== false || stripos($key, $departmentName) !== false) { return $color; }
        }
        return 'E0E0E0'; 
    }

    private function adjustColorBrightness(string $hexColor, int $percent): string
    {
        $hex = str_replace('#', '', $hexColor);
        if(strlen($hex) == 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        $r = min(255, $r + (255 - $r) * ($percent / 100)); $g = min(255, $g + (255 - $g) * ($percent / 100)); $b = min(255, $b + (255 - $b) * ($percent / 100));
        return sprintf('%02X%02X%02X', $r, $g, $b);
    }
}