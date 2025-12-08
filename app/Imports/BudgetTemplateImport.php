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

        // Debug: Log setiap baris yang dibaca
        \Log::info("Import Row {$rowNumber}", [
            'raw_row' => $row,
            'category_id' => $row['category_id'] ?? 'NULL',
        ]);

        $categoryId = $row['category_id'] ?? null;

        // Skip rows without category ID (department headers, section headers, empty rows)
        if (!$categoryId || empty($categoryId)) {
            \Log::info("Skipping row {$rowNumber} - no category ID");
            return null;
        }

        // Verify category belongs to this property
        $category = FinancialCategory::where('id', $categoryId)
            ->where('property_id', $this->propertyId)
            ->first();

        if (!$category) {
            $error = "Baris {$rowNumber}: Category ID {$categoryId} tidak ditemukan atau tidak termasuk dalam properti ini";
            $this->errors[] = $error;
            \Log::warning($error);
            return null;
        }

        // Only allow budget import for expense type categories
        if ($category->type !== 'expense') {
            $error = "Baris {$rowNumber}: Category ID {$categoryId} ({$category->name}) bukan kategori expense (type: {$category->type}) - hanya kategori expense yang bisa memiliki budget";
            $this->errors[] = $error;
            \Log::warning($error);
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

        $successCount = 0;
        $monthlyValues = [];

        foreach ($months as $monthNumber => $monthName) {
            $rawValue = $row[$monthName] ?? 0;
            $budgetValue = $rawValue;

            // Convert to numeric, handling formatted numbers with thousand separators
            if (is_string($budgetValue)) {
                $budgetValue = trim($budgetValue);

                // Remove thousand separators but preserve decimal separator
                // Common formats: "1,200,000.50" (US), "1.200.000,50" (EU/ID)
                // Strategy: Remove all non-digit chars except last dot/comma (which is decimal)

                // First, remove currency symbols and spaces
                $budgetValue = preg_replace('/[^\d,.\-]/', '', $budgetValue);

                // If there's a comma after a dot, it's EU format: 1.200,50 -> 1200.50
                if (strpos($budgetValue, '.') !== false && strrpos($budgetValue, ',') > strrpos($budgetValue, '.')) {
                    $budgetValue = str_replace('.', '', $budgetValue); // Remove thousand separator (dot)
                    $budgetValue = str_replace(',', '.', $budgetValue); // Convert decimal comma to dot
                }
                // Otherwise, it's US/standard format: 1,200.50 or just 1,200
                else {
                    $budgetValue = str_replace(',', '', $budgetValue); // Remove thousand separator (comma)
                }
            }
            $budgetValue = is_numeric($budgetValue) ? floatval($budgetValue) : 0;

            // Log conversion for debugging (only if value was converted from string)
            if (is_string($rawValue) && $rawValue !== '' && $budgetValue != $rawValue) {
                \Log::debug("Converted budget value for {$monthName}", [
                    'category_id' => $categoryId,
                    'month' => $monthName,
                    'raw_value' => $rawValue,
                    'parsed_value' => $budgetValue,
                ]);
            }

            $monthlyValues[$monthName] = $budgetValue;

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
            $successCount++;
        }

        $totalYearly = array_sum($monthlyValues);
        \Log::info("Successfully imported {$successCount} months for Category ID {$categoryId} ({$category->name})", [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'monthly_values' => $monthlyValues,
            'yearly_total' => $totalYearly,
            'average_monthly' => $totalYearly / 12,
        ]);

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
