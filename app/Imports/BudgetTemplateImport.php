<?php

namespace App\Imports;

use App\Models\FinancialEntry;
use App\Models\FinancialCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;

class BudgetTemplateImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $propertyId;
    protected $year;
    protected $importedCount = 0;
    protected $errors = [];
    protected $currentRow = 5; // Starting from row 5 (first data row after header at row 4)

    public function __construct(int $propertyId, int $year)
    {
        $this->propertyId = $propertyId;
        $this->year = $year;
    }

    public function headingRow(): int
    {
        return 4; // Header row is on row 4 (after title, year info, and empty row)
    }

    public function model(array $row)
    {
        $rowNumber = $this->currentRow++;
        $categoryId = $row['category_id'] ?? null;

        // Skip rows without category ID (department headers, section headers, empty rows)
        if (!$categoryId || empty($categoryId)) {
            return null;
        }

        // Verify category belongs to this property
        $category = FinancialCategory::where('id', $categoryId)
            ->where('property_id', $this->propertyId)
            ->first();

        if (!$category) {
            $this->errors[] = "Baris {$rowNumber}: Category ID {$categoryId} tidak ditemukan atau tidak termasuk dalam properti ini";
            return null;
        }

        // Only allow budget import for expense type categories
        if ($category->type !== 'expense') {
            $this->errors[] = "Baris {$rowNumber}: Category ID {$categoryId} ({$category->name}) bukan kategori expense - hanya kategori expense yang bisa memiliki budget";
            return null;
        }

        // Import budget for each month
        $months = [
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        foreach ($months as $monthNumber => $monthName) {
            $budgetValue = $row[$monthName] ?? 0;

            // Convert to numeric if it's a string
            $budgetValue = is_numeric($budgetValue) ? floatval($budgetValue) : 0;

            // Update or create financial entry (even for 0 values to ensure consistency)
            FinancialEntry::updateOrCreate(
                [
                    'property_id' => $this->propertyId,
                    'financial_category_id' => $categoryId,
                    'year' => $this->year,
                    'month' => $monthNumber,
                ],
                [
                    'budget_value' => $budgetValue,
                ]
            );

            $this->importedCount++;
        }

        return null; // We're not creating models, just updating entries
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer', // Made nullable to allow header/section rows
            'january' => 'nullable|numeric|min:0',
            'february' => 'nullable|numeric|min:0',
            'march' => 'nullable|numeric|min:0',
            'april' => 'nullable|numeric|min:0',
            'may' => 'nullable|numeric|min:0',
            'june' => 'nullable|numeric|min:0',
            'july' => 'nullable|numeric|min:0',
            'august' => 'nullable|numeric|min:0',
            'september' => 'nullable|numeric|min:0',
            'october' => 'nullable|numeric|min:0',
            'november' => 'nullable|numeric|min:0',
            'december' => 'nullable|numeric|min:0',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
