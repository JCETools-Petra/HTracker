<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetCategory;
use Illuminate\Support\Facades\DB;

class BudgetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $categories = $this->getUsaliCategories();

            foreach ($categories as $category) {
                BudgetCategory::updateOrCreate(
                    ['code' => $category['code']],
                    $category
                );
            }
        });

        $this->command->info('Budget categories seeded successfully!');
    }

    /**
     * Struktur kategori USALI untuk hotel.
     */
    private function getUsaliCategories(): array
    {
        return [
            // ========================================
            // REVENUE SECTION
            // ========================================

            // ROOMS REVENUE
            [
                'code' => '4000',
                'name' => 'ROOMS REVENUE',
                'type' => 'revenue',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 100,
                'property_id' => null,
            ],
            [
                'code' => '4010',
                'name' => 'Room Revenue - Offline',
                'type' => 'revenue',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 101,
                'property_id' => null,
            ],
            [
                'code' => '4020',
                'name' => 'Room Revenue - Online (OTA)',
                'type' => 'revenue',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 102,
                'property_id' => null,
            ],
            [
                'code' => '4030',
                'name' => 'Room Revenue - Travel Agent',
                'type' => 'revenue',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 103,
                'property_id' => null,
            ],
            [
                'code' => '4040',
                'name' => 'Room Revenue - Government',
                'type' => 'revenue',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 104,
                'property_id' => null,
            ],
            [
                'code' => '4050',
                'name' => 'Room Revenue - Corporate',
                'type' => 'revenue',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 105,
                'property_id' => null,
            ],

            // FOOD & BEVERAGE REVENUE
            [
                'code' => '4100',
                'name' => 'FOOD & BEVERAGE REVENUE',
                'type' => 'revenue',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 200,
                'property_id' => null,
            ],
            [
                'code' => '4110',
                'name' => 'Restaurant Revenue',
                'type' => 'revenue',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 201,
                'property_id' => null,
            ],
            [
                'code' => '4111',
                'name' => 'Breakfast Revenue',
                'type' => 'revenue',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 202,
                'property_id' => null,
            ],
            [
                'code' => '4112',
                'name' => 'Lunch Revenue',
                'type' => 'revenue',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 203,
                'property_id' => null,
            ],
            [
                'code' => '4113',
                'name' => 'Dinner Revenue',
                'type' => 'revenue',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 204,
                'property_id' => null,
            ],
            [
                'code' => '4120',
                'name' => 'Banquet & Catering Revenue',
                'type' => 'revenue',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 210,
                'property_id' => null,
            ],

            // OTHER REVENUE
            [
                'code' => '4200',
                'name' => 'OTHER OPERATING REVENUE',
                'type' => 'revenue',
                'department' => 'Other',
                'parent_id' => null,
                'sort_order' => 300,
                'property_id' => null,
            ],
            [
                'code' => '4210',
                'name' => 'MICE Room Revenue',
                'type' => 'revenue',
                'department' => 'Other',
                'parent_id' => null,
                'sort_order' => 301,
                'property_id' => null,
            ],
            [
                'code' => '4220',
                'name' => 'Laundry Revenue',
                'type' => 'revenue',
                'department' => 'Other',
                'parent_id' => null,
                'sort_order' => 302,
                'property_id' => null,
            ],
            [
                'code' => '4230',
                'name' => 'Spa & Wellness Revenue',
                'type' => 'revenue',
                'department' => 'Other',
                'parent_id' => null,
                'sort_order' => 303,
                'property_id' => null,
            ],
            [
                'code' => '4290',
                'name' => 'Miscellaneous Revenue',
                'type' => 'revenue',
                'department' => 'Other',
                'parent_id' => null,
                'sort_order' => 309,
                'property_id' => null,
            ],

            // ========================================
            // DEPARTMENTAL EXPENSES
            // ========================================

            // ROOMS EXPENSES
            [
                'code' => '5000',
                'name' => 'ROOMS DEPARTMENT EXPENSES',
                'type' => 'expense_variable',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 400,
                'property_id' => null,
            ],
            [
                'code' => '5010',
                'name' => 'Guest Supplies',
                'type' => 'expense_variable',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 401,
                'property_id' => null,
            ],
            [
                'code' => '5020',
                'name' => 'Cleaning Supplies',
                'type' => 'expense_variable',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 402,
                'property_id' => null,
            ],
            [
                'code' => '5030',
                'name' => 'Linen & Laundry',
                'type' => 'expense_variable',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 403,
                'property_id' => null,
            ],
            [
                'code' => '5040',
                'name' => 'Commission - OTA',
                'type' => 'expense_variable',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 404,
                'property_id' => null,
            ],

            // ROOMS PAYROLL
            [
                'code' => '5050',
                'name' => 'Rooms Payroll',
                'type' => 'payroll',
                'department' => 'Rooms',
                'parent_id' => null,
                'sort_order' => 410,
                'property_id' => null,
            ],

            // F&B EXPENSES
            [
                'code' => '5100',
                'name' => 'F&B DEPARTMENT EXPENSES',
                'type' => 'expense_variable',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 500,
                'property_id' => null,
            ],
            [
                'code' => '5110',
                'name' => 'Food Cost',
                'type' => 'expense_variable',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 501,
                'property_id' => null,
            ],
            [
                'code' => '5120',
                'name' => 'Beverage Cost',
                'type' => 'expense_variable',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 502,
                'property_id' => null,
            ],
            [
                'code' => '5130',
                'name' => 'Kitchen Supplies',
                'type' => 'expense_variable',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 503,
                'property_id' => null,
            ],

            // F&B PAYROLL
            [
                'code' => '5150',
                'name' => 'F&B Payroll',
                'type' => 'payroll',
                'department' => 'F&B',
                'parent_id' => null,
                'sort_order' => 510,
                'property_id' => null,
            ],

            // ========================================
            // UNDISTRIBUTED OPERATING EXPENSES
            // ========================================

            // ADMINISTRATIVE & GENERAL
            [
                'code' => '6000',
                'name' => 'ADMINISTRATIVE & GENERAL',
                'type' => 'expense_fixed',
                'department' => 'Admin',
                'parent_id' => null,
                'sort_order' => 600,
                'property_id' => null,
            ],
            [
                'code' => '6010',
                'name' => 'Office Supplies',
                'type' => 'expense_fixed',
                'department' => 'Admin',
                'parent_id' => null,
                'sort_order' => 601,
                'property_id' => null,
            ],
            [
                'code' => '6020',
                'name' => 'Printing & Stationery',
                'type' => 'expense_fixed',
                'department' => 'Admin',
                'parent_id' => null,
                'sort_order' => 602,
                'property_id' => null,
            ],
            [
                'code' => '6030',
                'name' => 'Professional Fees',
                'type' => 'expense_fixed',
                'department' => 'Admin',
                'parent_id' => null,
                'sort_order' => 603,
                'property_id' => null,
            ],
            [
                'code' => '6040',
                'name' => 'Banking & Credit Card Fees',
                'type' => 'expense_fixed',
                'department' => 'Admin',
                'parent_id' => null,
                'sort_order' => 604,
                'property_id' => null,
            ],
            [
                'code' => '6050',
                'name' => 'Admin Payroll',
                'type' => 'payroll',
                'department' => 'Admin',
                'parent_id' => null,
                'sort_order' => 610,
                'property_id' => null,
            ],

            // SALES & MARKETING
            [
                'code' => '6100',
                'name' => 'SALES & MARKETING',
                'type' => 'expense_fixed',
                'department' => 'Marketing',
                'parent_id' => null,
                'sort_order' => 700,
                'property_id' => null,
            ],
            [
                'code' => '6110',
                'name' => 'Advertising & Promotion',
                'type' => 'expense_variable',
                'department' => 'Marketing',
                'parent_id' => null,
                'sort_order' => 701,
                'property_id' => null,
            ],
            [
                'code' => '6120',
                'name' => 'Digital Marketing',
                'type' => 'expense_variable',
                'department' => 'Marketing',
                'parent_id' => null,
                'sort_order' => 702,
                'property_id' => null,
            ],
            [
                'code' => '6150',
                'name' => 'Marketing Payroll',
                'type' => 'payroll',
                'department' => 'Marketing',
                'parent_id' => null,
                'sort_order' => 710,
                'property_id' => null,
            ],

            // PROPERTY OPERATIONS, MAINTENANCE & ENERGY (POMEC)
            [
                'code' => '6200',
                'name' => 'PROPERTY OPERATIONS & MAINTENANCE',
                'type' => 'expense_fixed',
                'department' => 'Maintenance',
                'parent_id' => null,
                'sort_order' => 800,
                'property_id' => null,
            ],
            [
                'code' => '6210',
                'name' => 'Electricity',
                'type' => 'expense_variable',
                'department' => 'Maintenance',
                'parent_id' => null,
                'sort_order' => 801,
                'property_id' => null,
            ],
            [
                'code' => '6220',
                'name' => 'Water & Gas',
                'type' => 'expense_variable',
                'department' => 'Maintenance',
                'parent_id' => null,
                'sort_order' => 802,
                'property_id' => null,
            ],
            [
                'code' => '6230',
                'name' => 'Repairs & Maintenance',
                'type' => 'expense_variable',
                'department' => 'Maintenance',
                'parent_id' => null,
                'sort_order' => 803,
                'property_id' => null,
            ],
            [
                'code' => '6240',
                'name' => 'Building Maintenance Supplies',
                'type' => 'expense_variable',
                'department' => 'Maintenance',
                'parent_id' => null,
                'sort_order' => 804,
                'property_id' => null,
            ],
            [
                'code' => '6250',
                'name' => 'Maintenance Payroll',
                'type' => 'payroll',
                'department' => 'Maintenance',
                'parent_id' => null,
                'sort_order' => 810,
                'property_id' => null,
            ],

            // UTILITIES
            [
                'code' => '6300',
                'name' => 'UTILITIES',
                'type' => 'expense_fixed',
                'department' => 'Utilities',
                'parent_id' => null,
                'sort_order' => 900,
                'property_id' => null,
            ],
            [
                'code' => '6310',
                'name' => 'Internet & Telephone',
                'type' => 'expense_fixed',
                'department' => 'Utilities',
                'parent_id' => null,
                'sort_order' => 901,
                'property_id' => null,
            ],
            [
                'code' => '6320',
                'name' => 'Cable TV',
                'type' => 'expense_fixed',
                'department' => 'Utilities',
                'parent_id' => null,
                'sort_order' => 902,
                'property_id' => null,
            ],

            // FIXED CHARGES
            [
                'code' => '7000',
                'name' => 'FIXED CHARGES',
                'type' => 'expense_fixed',
                'department' => 'Fixed',
                'parent_id' => null,
                'sort_order' => 1000,
                'property_id' => null,
            ],
            [
                'code' => '7010',
                'name' => 'Property Tax',
                'type' => 'expense_fixed',
                'department' => 'Fixed',
                'parent_id' => null,
                'sort_order' => 1001,
                'property_id' => null,
            ],
            [
                'code' => '7020',
                'name' => 'Insurance',
                'type' => 'expense_fixed',
                'department' => 'Fixed',
                'parent_id' => null,
                'sort_order' => 1002,
                'property_id' => null,
            ],
            [
                'code' => '7030',
                'name' => 'License & Permits',
                'type' => 'expense_fixed',
                'department' => 'Fixed',
                'parent_id' => null,
                'sort_order' => 1003,
                'property_id' => null,
            ],
        ];
    }
}
