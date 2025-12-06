# Advanced Hotel Budgeting & P&L Reporting - User Guide

## Overview

This feature implements a comprehensive **Financial Budgeting and P&L (Profit & Loss) Reporting System** following **USALI (Uniform System of Accounts for the Lodging Industry)** standards with hierarchical category structure.

## Key Features

### 1. Hybrid Revenue & Expense Tracking
- **Revenue (Income)**: Automatically pulled from existing `DailyIncome` data
- **Expenses**: Manually inputted by property users on a monthly basis

### 2. USALI-Compliant Category Structure
The system implements a comprehensive hierarchical structure with the following departments:

#### Revenue Categories
- **Room Revenue** (Auto-calculated from `DailyIncome.total_rooms_revenue`)
- **F&B Revenue** (Auto-calculated from `DailyIncome.total_fb_revenue`)
- **Other Revenue**

#### Expense Departments
1. **Front Office**
   - Payroll & Related Expenses
   - Other Expenses (supplies, telecommunications, etc.)

2. **Housekeeping**
   - Payroll & Related Expenses
   - Other Expenses (cleaning supplies, linen, etc.)

3. **F&B Product (Kitchen)**
   - Cost of Goods Sold (Food Cost)
   - Payroll & Related Expenses
   - Other Expenses (fuel, utensils, etc.)

4. **F&B Service**
   - Cost of Goods Sold (Beverage Cost)
   - Payroll & Related Expenses
   - Other Expenses (glassware, entertainment, etc.)

5. **POMAC (Property Operation, Maintenance & Energy)**
   - Payroll & Related Expenses
   - Energy Costs (electricity, water, fuel)
   - Maintenance Expenses

6. **Accounting & General (A&G)**
   - Payroll & Related Expenses
   - Other Expenses (audit, legal, office supplies, etc.)

### 3. Automatic Recursive Calculations
- Parent categories automatically sum up all child values
- Real-time variance calculation (Actual vs Budget)
- Year-to-Date (YTD) calculations

## Installation & Setup

### Step 1: Run Migrations
```bash
php artisan migrate
```

This will create two new tables:
- `financial_categories` - Stores hierarchical account structure
- `financial_entries` - Stores actual and budget values

### Step 2: Seed Financial Categories
```bash
php artisan db:seed --class=FinancialCategorySeeder
```

This will populate the USALI-compliant category structure for all existing properties.

**Note**: The seeder automatically runs for all properties. If you add a new property later, you need to run the seeder again or manually trigger it for that specific property.

## User Guide

### Accessing Financial Reports

1. **Login** as a property user (`pengguna_properti` role)
2. Navigate to **Financial** menu
3. You'll see two main options:
   - **Input Data Aktual** - For monthly expense input
   - **Laporan P&L** - For viewing P&L reports

### Inputting Monthly Expenses

1. Go to **Input Data Aktual** (`/property/financial/input-actual`)
2. Select **Year** and **Month** from the dropdown
3. Use **Department Tabs** to navigate between different departments:
   - Front Office
   - Housekeeping
   - F&B Product
   - F&B Service
   - POMAC
   - Accounting & General

4. For each category, enter:
   - **Actual (Rp)**: Realized expenses for the month
   - **Budget (Rp)**: Planned budget for the month

5. Click **Simpan Data** to save

**Notes**:
- Yellow-highlighted rows indicate **Payroll** categories
- Categories marked with **(Auto)** are automatically calculated and cannot be edited
- Only leaf-level categories (lowest in hierarchy) can be manually inputted

### Viewing P&L Reports

1. Go to **Laporan P&L** (`/property/financial/report`)
2. Select **Year** and **Month**
3. Click **Tampilkan**

The report displays:
- **Current Month** columns: Actual, Budget, Variance
- **Year-to-Date (YTD)** columns: Actual, Budget, Variance
- Hierarchical indentation showing parent-child relationships
- Color-coded sections:
  - **Green**: Revenue section
  - **Red**: Expenses section
  - **Blue**: Gross Operating Profit (GOP)

**Variance Interpretation**:
- For **Revenue**: Positive variance (green) is good
- For **Expenses**: Negative variance (green) is good (spending less than budget)

### Setting Annual Budget

1. Go to **Input Budget** (`/property/financial/input-budget`)
2. Select the **Year** for budgeting (typically next year)
3. Enter annual budget amounts for each category
4. Click **Simpan**

The system will automatically distribute the annual budget equally across 12 months.

## Technical Architecture

### Database Schema

#### `financial_categories` Table
```
- id (PK)
- property_id (FK to properties)
- parent_id (FK to financial_categories, nullable)
- name (Category name)
- code (Special code for auto-mapping, e.g., 'ROOM_REV')
- type (enum: revenue, expense, calculated)
- is_payroll (boolean)
- sort_order (integer)
```

#### `financial_entries` Table
```
- id (PK)
- property_id (FK to properties)
- financial_category_id (FK to financial_categories)
- year (Year)
- month (1-12)
- actual_value (decimal 15,2)
- budget_value (decimal 15,2)
- UNIQUE(property_id, financial_category_id, year, month)
```

### Key Models

1. **FinancialCategory**
   - Self-referencing relationship (`parent`, `children`, `descendants`)
   - Scopes: `roots()`, `byType()`, `payroll()`, `forProperty()`
   - Helper methods: `hasChildren()`, `isLeaf()`, `allowsManualInput()`

2. **FinancialEntry**
   - Stores actual and budget values
   - Scopes: `forProperty()`, `forYear()`, `forMonth()`, `forPeriod()`

### Service Layer

**FinancialReportService** provides:
- `getPnL($propertyId, $year, $month)` - Generate complete P&L report
- `getCategoriesForInput($propertyId)` - Get categories for input form
- `saveEntry(...)` - Save/update financial entry

The service implements:
- **Recursive calculation** of parent category values
- **Hybrid data fetching**: Auto from `DailyIncome`, manual from `financial_entries`
- **YTD calculations**: Sum of Jan to current month

### Routes

All routes are under `/property` prefix with `pengguna_properti,owner` role middleware:

```
GET  /property/financial/input-actual          -> Show input form
POST /property/financial/input-actual          -> Save monthly data
GET  /property/financial/input-budget          -> Show budget form
POST /property/financial/input-budget          -> Save annual budget
GET  /property/financial/report                -> Show P&L report
```

## Code Mapping Logic

The system uses special `code` values to map categories to automatic data sources:

- `ROOM_REV` → `DailyIncome.total_rooms_revenue`
- `FNB_REV` → `DailyIncome.total_fb_revenue`

To add new auto-calculated categories, update:
1. The seeder to add the category with appropriate code
2. `FinancialReportService::getAutoCalculatedValues()` method

## Customization Guide

### Adding New Expense Categories

1. Edit `FinancialCategorySeeder.php`
2. Add your category using `createCategory()` method:
```php
$this->createCategory(
    $propertyId,        // Property ID
    $parentId,          // Parent category ID (null for root)
    'Category Name',    // Display name
    null,               // Code (null for manual input)
    'expense',          // Type
    false,              // is_payroll flag
    $sortOrder          // Sort order
);
```
3. Re-run seeder: `php artisan db:seed --class=FinancialCategorySeeder`

### Modifying Report Layout

Edit the views:
- `/resources/views/financial/report.blade.php` - Main report layout
- `/resources/views/financial/partials/category-row.blade.php` - Category row rendering

## Troubleshooting

### Categories not showing up
- Ensure you ran the seeder after migration
- Check that the property exists before running seeder
- Verify `property_id` foreign key constraints

### Revenue showing as zero
- Check that `DailyIncome` data exists for the selected period
- Verify the `code` values ('ROOM_REV', 'FNB_REV') match in both seeder and service

### Budget values not distributing
- Ensure you're using the `/input-budget` route for annual budget
- Check that the year is correct
- Verify database entries were created for all 12 months

## Best Practices

1. **Monthly Routine**:
   - Input daily income data first (`DailyIncome`)
   - Then input monthly expenses via Financial module
   - Review P&L report at month-end

2. **Budget Planning**:
   - Set annual budgets at the beginning of each year
   - Review and adjust quarterly if needed

3. **Data Integrity**:
   - Don't manually edit auto-calculated categories
   - Keep payroll costs separate from operational expenses
   - Regularly backup financial data

## Support & Maintenance

For issues or feature requests, contact the development team or create an issue in the project repository.

---

**Version**: 1.0
**Last Updated**: December 2025
**USALI Compliance**: 11th Revised Edition
