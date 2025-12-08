<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;

class AddPayrollToFBProductSeeder extends Seeder
{
    public function run()
    {
        // Find F&B Product department
        $fbProduct = FinancialCategory::where('name', 'F&B Product (Kitchen)')
            ->whereNull('parent_id')
            ->first();

        if (!$fbProduct) {
            $this->command->error('F&B Product department not found!');
            return;
        }

        $this->command->info("Found F&B Product department (ID: {$fbProduct->id})");

        // Get property_id from the department
        $propertyId = $fbProduct->property_id;

        // Check if PAYROLL section already exists
        $existingPayroll = FinancialCategory::where('parent_id', $fbProduct->id)
            ->where('name', 'PAYROLL & RELATED EXPENSES')
            ->first();

        if ($existingPayroll) {
            $this->command->warn('PAYROLL & RELATED EXPENSES already exists for F&B Product!');
            return;
        }

        // Get highest sort_order for F&B Product children
        $maxSortOrder = FinancialCategory::where('parent_id', $fbProduct->id)
            ->max('sort_order') ?? 0;

        // Create PAYROLL & RELATED EXPENSES section
        $this->command->info('Creating PAYROLL & RELATED EXPENSES section...');
        $payrollSection = FinancialCategory::create([
            'property_id' => $propertyId,
            'parent_id' => $fbProduct->id,
            'name' => 'PAYROLL & RELATED EXPENSES',
            'code' => 'FB_PAYROLL',
            'type' => 'expense',
            'is_payroll' => false, // This is the section header, not actual payroll
            'sort_order' => $maxSortOrder + 10,
        ]);

        $this->command->info("Created section (ID: {$payrollSection->id})");

        // Define payroll sub-categories
        $payrollCategories = [
            ['name' => 'SALARIES & WAGES', 'code' => 'FB_SALARIES', 'is_payroll' => true],
            ['name' => 'LEBARAN BONUS', 'code' => 'FB_LEBARAN', 'is_payroll' => true],
            ['name' => 'EMPLOYEE TRANSPORTATION', 'code' => 'FB_TRANSPORT', 'is_payroll' => true],
            ['name' => 'MEDICAL EXPENSES', 'code' => 'FB_MEDICAL', 'is_payroll' => true],
            ['name' => 'STAFF MEALS', 'code' => 'FB_MEALS', 'is_payroll' => true],
            ['name' => 'JAMSOSTEK', 'code' => 'FB_JAMSOSTEK', 'is_payroll' => true],
            ['name' => 'TEMPORARY WORKERS', 'code' => 'FB_TEMP', 'is_payroll' => true],
            ['name' => 'STAFF AWARD', 'code' => 'FB_AWARD', 'is_payroll' => true],
        ];

        $this->command->info('Creating sub-categories...');
        $sortOrder = 10;

        foreach ($payrollCategories as $cat) {
            $category = FinancialCategory::create([
                'property_id' => $propertyId,
                'parent_id' => $payrollSection->id,
                'name' => $cat['name'],
                'code' => $cat['code'],
                'type' => 'expense',
                'is_payroll' => $cat['is_payroll'],
                'sort_order' => $sortOrder,
            ]);

            $this->command->info("  ✓ Created: {$cat['name']} (ID: {$category->id})");
            $sortOrder += 10;
        }

        $this->command->info("\n✅ Successfully added PAYROLL & RELATED EXPENSES to F&B Product!");
        $this->command->info("Total sub-categories created: " . count($payrollCategories));
    }
}
