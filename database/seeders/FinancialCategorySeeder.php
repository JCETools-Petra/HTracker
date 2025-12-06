<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use App\Models\Property;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates a comprehensive USALI-compliant financial category structure
     * for all properties in the system.
     */
    public function run(): void
    {
        // Get all properties
        $properties = Property::all();

        foreach ($properties as $property) {
            $this->seedForProperty($property->id);
        }
    }

    /**
     * Seed financial categories for a specific property.
     */
    private function seedForProperty(int $propertyId): void
    {
        // Clear existing categories for this property
        FinancialCategory::where('property_id', $propertyId)->delete();

        $sortOrder = 1;

        // ============================================================
        // A. REVENUE SECTION
        // ============================================================

        // 1. Room Revenue (Auto-calculated from DailyIncome)
        $roomRevenue = $this->createCategory($propertyId, null, 'Room Revenue', 'ROOM_REV', 'revenue', false, $sortOrder++);

        // 2. F&B Revenue (Auto-calculated from DailyIncome)
        $fnbRevenue = $this->createCategory($propertyId, null, 'F&B Revenue', 'FNB_REV', 'revenue', false, $sortOrder++);

        // 3. Other Revenue
        $otherRevenue = $this->createCategory($propertyId, null, 'Other Revenue', null, 'revenue', false, $sortOrder++);

        // ============================================================
        // B. EXPENSES SECTION - Departmental Breakdown
        // ============================================================

        // -------------------- 1. FRONT OFFICE --------------------
        $frontOffice = $this->createCategory($propertyId, null, 'Front Office', null, 'expense', false, $sortOrder++);

        // Front Office - Payroll & Related Expenses
        $foPayroll = $this->createCategory($propertyId, $frontOffice->id, 'Payroll & Related Expenses', null, 'expense', true, 1);
        $this->createCategory($propertyId, $foPayroll->id, 'Salaries & Wages', null, 'expense', false, 1);
        $this->createCategory($propertyId, $foPayroll->id, 'Service Charge Distribution', null, 'expense', false, 2);
        $this->createCategory($propertyId, $foPayroll->id, 'Employee Benefits / BPJS', null, 'expense', false, 3);
        $this->createCategory($propertyId, $foPayroll->id, 'Uniforms (Personnel)', null, 'expense', false, 4);

        // Front Office - Other Expenses
        $foOther = $this->createCategory($propertyId, $frontOffice->id, 'Other Expenses', null, 'expense', false, 2);
        $this->createCategory($propertyId, $foOther->id, 'Cleaning Supplies (FO)', null, 'expense', false, 1);
        $this->createCategory($propertyId, $foOther->id, 'Guest Supplies (Amenities)', null, 'expense', false, 2);
        $this->createCategory($propertyId, $foOther->id, 'Printing & Stationery', null, 'expense', false, 3);
        $this->createCategory($propertyId, $foOther->id, 'Telecommunications', null, 'expense', false, 4);
        $this->createCategory($propertyId, $foOther->id, 'Transportation', null, 'expense', false, 5);
        $this->createCategory($propertyId, $foOther->id, 'Entertainment', null, 'expense', false, 6);
        $this->createCategory($propertyId, $foOther->id, 'Operating Supplies', null, 'expense', false, 7);

        // -------------------- 2. HOUSEKEEPING --------------------
        $housekeeping = $this->createCategory($propertyId, null, 'Housekeeping', null, 'expense', false, $sortOrder++);

        // Housekeeping - Payroll & Related Expenses
        $hkPayroll = $this->createCategory($propertyId, $housekeeping->id, 'Payroll & Related Expenses', null, 'expense', true, 1);
        $this->createCategory($propertyId, $hkPayroll->id, 'Salaries & Wages', null, 'expense', false, 1);
        $this->createCategory($propertyId, $hkPayroll->id, 'Service Charge', null, 'expense', false, 2);
        $this->createCategory($propertyId, $hkPayroll->id, 'Contract Labor (Outsourcing)', null, 'expense', false, 3);

        // Housekeeping - Other Expenses
        $hkOther = $this->createCategory($propertyId, $housekeeping->id, 'Other Expenses', null, 'expense', false, 2);
        $this->createCategory($propertyId, $hkOther->id, 'Cleaning Supplies (Chemicals)', null, 'expense', false, 1);
        $this->createCategory($propertyId, $hkOther->id, 'Guest Supplies (Amenities/Toiletries)', null, 'expense', false, 2);
        $this->createCategory($propertyId, $hkOther->id, 'Laundry Supplies', null, 'expense', false, 3);
        $this->createCategory($propertyId, $hkOther->id, 'Linen & Towels', null, 'expense', false, 4);
        $this->createCategory($propertyId, $hkOther->id, 'Uniforms', null, 'expense', false, 5);
        $this->createCategory($propertyId, $hkOther->id, 'Decorations / Flowers', null, 'expense', false, 6);

        // -------------------- 3. F&B PRODUCT (Kitchen) --------------------
        $fnbProduct = $this->createCategory($propertyId, null, 'F&B Product (Kitchen)', null, 'expense', false, $sortOrder++);

        // F&B Product - Cost of Goods Sold (COGS)
        $fnbCogs = $this->createCategory($propertyId, $fnbProduct->id, 'Cost of Goods Sold (COGS)', null, 'expense', false, 1);
        $this->createCategory($propertyId, $fnbCogs->id, 'Food Cost', null, 'expense', false, 1);

        // F&B Product - Payroll & Related Expenses
        $fnbProductPayroll = $this->createCategory($propertyId, $fnbProduct->id, 'Payroll & Related Expenses', null, 'expense', true, 2);
        $this->createCategory($propertyId, $fnbProductPayroll->id, 'Kitchen Salaries', null, 'expense', false, 1);
        $this->createCategory($propertyId, $fnbProductPayroll->id, 'Service Charge', null, 'expense', false, 2);

        // F&B Product - Other Expenses
        $fnbProductOther = $this->createCategory($propertyId, $fnbProduct->id, 'Other Expenses', null, 'expense', false, 3);
        $this->createCategory($propertyId, $fnbProductOther->id, 'Kitchen Fuel / Gas', null, 'expense', false, 1);
        $this->createCategory($propertyId, $fnbProductOther->id, 'Cleaning Supplies (Kitchen)', null, 'expense', false, 2);
        $this->createCategory($propertyId, $fnbProductOther->id, 'Utensils & Chinaware', null, 'expense', false, 3);

        // -------------------- 4. F&B SERVICE --------------------
        $fnbService = $this->createCategory($propertyId, null, 'F&B Service', null, 'expense', false, $sortOrder++);

        // F&B Service - Cost of Goods Sold (COGS)
        $fnbServiceCogs = $this->createCategory($propertyId, $fnbService->id, 'Cost of Goods Sold (COGS)', null, 'expense', false, 1);
        $this->createCategory($propertyId, $fnbServiceCogs->id, 'Beverage Cost', null, 'expense', false, 1);

        // F&B Service - Payroll & Related Expenses
        $fnbServicePayroll = $this->createCategory($propertyId, $fnbService->id, 'Payroll & Related Expenses', null, 'expense', true, 2);
        $this->createCategory($propertyId, $fnbServicePayroll->id, 'Service Staff Salaries', null, 'expense', false, 1);
        $this->createCategory($propertyId, $fnbServicePayroll->id, 'Service Charge', null, 'expense', false, 2);

        // F&B Service - Other Expenses
        $fnbServiceOther = $this->createCategory($propertyId, $fnbService->id, 'Other Expenses', null, 'expense', false, 3);
        $this->createCategory($propertyId, $fnbServiceOther->id, 'Glassware & Tableware', null, 'expense', false, 1);
        $this->createCategory($propertyId, $fnbServiceOther->id, 'Decorations', null, 'expense', false, 2);
        $this->createCategory($propertyId, $fnbServiceOther->id, 'Music & Entertainment', null, 'expense', false, 3);
        $this->createCategory($propertyId, $fnbServiceOther->id, 'Printing (Menu)', null, 'expense', false, 4);

        // -------------------- 5. POMAC (Property Operation Maintenance & Energy Cost) --------------------
        $pomac = $this->createCategory($propertyId, null, 'POMAC (Property Operation, Maintenance & Energy)', null, 'expense', false, $sortOrder++);

        // POMAC - Payroll & Related Expenses
        $pomacPayroll = $this->createCategory($propertyId, $pomac->id, 'Payroll & Related Expenses', null, 'expense', true, 1);
        $this->createCategory($propertyId, $pomacPayroll->id, 'Engineering Salaries', null, 'expense', false, 1);

        // POMAC - Energy Costs
        $pomacEnergy = $this->createCategory($propertyId, $pomac->id, 'Energy Costs', null, 'expense', false, 2);
        $this->createCategory($propertyId, $pomacEnergy->id, 'Electricity (PLN)', null, 'expense', false, 1);
        $this->createCategory($propertyId, $pomacEnergy->id, 'Water (PDAM)', null, 'expense', false, 2);
        $this->createCategory($propertyId, $pomacEnergy->id, 'Fuel / Diesel (Genset)', null, 'expense', false, 3);

        // POMAC - Maintenance Expenses
        $pomacMaintenance = $this->createCategory($propertyId, $pomac->id, 'Maintenance Expenses', null, 'expense', false, 3);
        $this->createCategory($propertyId, $pomacMaintenance->id, 'Building Repairs', null, 'expense', false, 1);
        $this->createCategory($propertyId, $pomacMaintenance->id, 'Electrical & Mechanical Equipment', null, 'expense', false, 2);
        $this->createCategory($propertyId, $pomacMaintenance->id, 'Painting & Decoration', null, 'expense', false, 3);
        $this->createCategory($propertyId, $pomacMaintenance->id, 'Waste Removal', null, 'expense', false, 4);

        // -------------------- 6. ACCOUNTING & GENERAL (A&G) --------------------
        $accountingGeneral = $this->createCategory($propertyId, null, 'Accounting & General (A&G)', null, 'expense', false, $sortOrder++);

        // A&G - Payroll & Related Expenses
        $agPayroll = $this->createCategory($propertyId, $accountingGeneral->id, 'Payroll & Related Expenses', null, 'expense', true, 1);
        $this->createCategory($propertyId, $agPayroll->id, 'Admin Salaries', null, 'expense', false, 1);

        // A&G - Other Expenses
        $agOther = $this->createCategory($propertyId, $accountingGeneral->id, 'Other Expenses', null, 'expense', false, 2);
        $this->createCategory($propertyId, $agOther->id, 'Audit & Legal Fees', null, 'expense', false, 1);
        $this->createCategory($propertyId, $agOther->id, 'Travel Expenses', null, 'expense', false, 2);
        $this->createCategory($propertyId, $agOther->id, 'Permits & Licenses', null, 'expense', false, 3);
        $this->createCategory($propertyId, $agOther->id, 'Office Supplies', null, 'expense', false, 4);
        $this->createCategory($propertyId, $agOther->id, 'Bank Charges', null, 'expense', false, 5);
    }

    /**
     * Helper method to create a category.
     */
    private function createCategory(
        int $propertyId,
        ?int $parentId,
        string $name,
        ?string $code,
        string $type,
        bool $isPayroll,
        int $sortOrder
    ): FinancialCategory {
        return FinancialCategory::create([
            'property_id' => $propertyId,
            'parent_id' => $parentId,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'is_payroll' => $isPayroll,
            'sort_order' => $sortOrder,
        ]);
    }
}
