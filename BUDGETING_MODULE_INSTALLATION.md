# Hotel Budgeting Module - Installation Guide

Modul Budgeting Hotel berbasis USALI (Uniform System of Accounts for the Lodging Industry) untuk HTracker.

## 🎯 Fitur Utama

1. **Budget Planning** - Buat rencana budget tahunan per properti
2. **USALI Chart of Accounts** - Struktur akun standar hotel yang komprehensif
3. **Budget Drivers** - Input target Occupancy % dan ADR per bulan
4. **Excel-like Grid Input** - Interface input yang familiar seperti Excel
5. **Budget vs Actual Report** - Laporan perbandingan Budget dengan Data Aktual
6. **Workflow Management** - Status Draft → Approved → Locked

## 📦 File yang Ditambahkan

### Migrations
- `2025_12_05_000001_create_budget_periods_table.php`
- `2025_12_05_000002_create_budget_categories_table.php`
- `2025_12_05_000003_create_budget_plans_table.php`
- `2025_12_05_000004_create_budget_drivers_table.php`

### Models
- `app/Models/BudgetPeriod.php`
- `app/Models/BudgetCategory.php`
- `app/Models/BudgetPlan.php`
- `app/Models/BudgetDriver.php`

### Controllers
- `app/Http/Controllers/Admin/BudgetController.php`

### Views
- `resources/views/admin/budgets/index.blade.php`
- `resources/views/admin/budgets/create.blade.php`
- `resources/views/admin/budgets/show.blade.php`
- `resources/views/admin/budgets/report.blade.php`

### Seeders
- `database/seeders/BudgetCategorySeeder.php`

## 🚀 Cara Instalasi

### 1. Jalankan Migrations

```bash
php artisan migrate
```

Ini akan membuat 4 tabel baru:
- `budget_periods` - Menyimpan periode budget tahunan
- `budget_categories` - Master data kategori akun USALI
- `budget_plans` - Data budget per kategori per bulan
- `budget_drivers` - Target Occupancy dan ADR per bulan

### 2. Seed Budget Categories (USALI Chart of Accounts)

```bash
php artisan db:seed --class=BudgetCategorySeeder
```

Seeder ini akan mengisi tabel `budget_categories` dengan struktur akun standar hotel:

**REVENUE:**
- Room Revenue (Offline, Online/OTA, Travel Agent, Government, Corporate)
- F&B Revenue (Breakfast, Lunch, Dinner, Banquet)
- Other Operating Revenue (MICE, Laundry, Spa, Miscellaneous)

**EXPENSES:**
- Rooms Department (Guest Supplies, Cleaning Supplies, Linen, OTA Commission)
- F&B Department (Food Cost, Beverage Cost, Kitchen Supplies)
- Administrative & General (Office Supplies, Professional Fees, Banking Fees)
- Sales & Marketing (Advertising, Digital Marketing)
- Property Operations & Maintenance (Electricity, Water, Repairs)
- Utilities (Internet, Cable TV)
- Fixed Charges (Property Tax, Insurance, Licenses)

### 3. Akses Modul Budgeting

Setelah instalasi, akses modul budgeting melalui:

```
/admin/properties/{property_id}/budgets
```

Atau tambahkan link di navigasi properties show page.

## 📊 Cara Menggunakan

### 1. Membuat Budget Baru

1. Pilih properti yang ingin dibuatkan budget
2. Klik "Budget Planner" atau akses `/admin/properties/{id}/budgets`
3. Klik tombol "+ Buat Budget Baru"
4. Pilih tahun budget (sistem akan mencegah duplikasi tahun)
5. Sistem akan otomatis membuat template untuk 12 bulan

### 2. Input Budget Planning

Di grid input budget:

**Budget Drivers (Bagian Atas):**
- Input **Target Occupancy %** untuk setiap bulan
- Input **Target ADR** (Average Daily Rate) untuk setiap bulan
- Sistem akan auto-calculate **Room Revenue** berdasarkan formula:
  ```
  Room Revenue = (Total Rooms × Days in Month × Occupancy%) × ADR
  ```

**Financial Categories:**
- Input nominal budget untuk setiap kategori per bulan
- Total tahunan akan dihitung otomatis di kolom kanan
- Budget dapat disimpan kapan saja (status Draft)

### 3. Workflow Budget Status

- **Draft** - Budget bisa diedit bebas
- **Approved** - Budget sudah disetujui, masih bisa di-lock
- **Locked** - Budget terkunci, tidak bisa diedit lagi (untuk proteksi data)

### 4. Melihat Laporan P&L (Budget vs Actual)

1. Dari daftar budget, klik "Lihat Laporan"
2. Pilih bulan yang ingin dibandingkan
3. Laporan akan menampilkan:
   - **Budget Amount** - Nilai yang direncanakan
   - **Actual Amount** - Data aktual dari `daily_incomes` table
   - **Variance (Rp)** - Selisih dalam Rupiah
   - **Variance (%)** - Selisih dalam persentase
   - **Summary Cards** - Total Revenue, Expenses, Net Profit

## 🔗 Relasi Database

### Mapping: Daily Incomes → Budget Categories

Sistem otomatis memetakan data dari tabel `daily_incomes` ke `budget_categories` dengan kode:

```php
'4010' => offline_room_income
'4020' => online_room_income
'4030' => ta_income
'4040' => gov_income
'4050' => corp_income
'4111' => breakfast_income
'4112' => lunch_income
'4113' => dinner_income
'4210' => mice_room_income
'4290' => others_income
```

Jika ingin menambah mapping lainnya, edit method `getActualData()` di `BudgetController.php`.

## 🛠️ Customisasi

### Menambah Kategori Akun Baru

Edit file `database/seeders/BudgetCategorySeeder.php`, tambahkan entry baru di array `getUsaliCategories()`:

```php
[
    'code' => '4300',
    'name' => 'Parking Revenue',
    'type' => 'revenue',
    'department' => 'Other',
    'parent_id' => null,
    'sort_order' => 310,
    'property_id' => null,
],
```

Kemudian jalankan seeder lagi:
```bash
php artisan db:seed --class=BudgetCategorySeeder
```

### Mengubah Mapping Actual Data

Edit method `getActualData()` di `app/Http/Controllers/Admin/BudgetController.php`:

```php
private function getActualData($propertyId, $year, $month)
{
    $dailyIncomes = DailyIncome::where('property_id', $propertyId)
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->get();

    return [
        '4010' => $dailyIncomes->sum('offline_room_income'),
        // Tambahkan mapping baru di sini
        '4300' => $dailyIncomes->sum('parking_income'),
    ];
}
```

## 📝 Routes yang Tersedia

```php
GET    /admin/properties/{property}/budgets              - Daftar budget periods
GET    /admin/properties/{property}/budgets/create       - Form buat budget baru
POST   /admin/properties/{property}/budgets              - Store budget baru
GET    /admin/properties/{property}/budgets/{period}     - Grid input budget
PUT    /admin/properties/{property}/budgets/{period}     - Update budget data
PATCH  /admin/properties/{property}/budgets/{period}/status - Update status
DELETE /admin/properties/{property}/budgets/{period}     - Delete budget
GET    /admin/properties/{property}/budgets/{period}/report - Laporan P&L
```

## 🔐 Authorization

Modul budgeting dilindungi dengan middleware:
```php
middleware(['auth', 'verified', 'role:admin,owner'])
```

Hanya user dengan role **admin** atau **owner** yang bisa akses modul ini.

## 💡 Tips & Best Practices

1. **Buat budget di awal tahun** - Idealnya budget dibuat sebelum tahun berjalan
2. **Lock budget setelah approved** - Hindari perubahan data historis
3. **Review variance bulanan** - Gunakan laporan P&L untuk monitoring performa
4. **Update actual data harian** - Pastikan data `daily_incomes` terupdate untuk akurasi laporan
5. **Backup sebelum delete** - Budget yang di-delete tidak bisa di-restore

## 🐛 Troubleshooting

### Budget tidak muncul di laporan
- Pastikan property memiliki data di tabel `daily_incomes` untuk periode yang dipilih
- Periksa mapping kategori di method `getActualData()`

### Auto-calculate Room Revenue tidak jalan
- Pastikan kolom `total_rooms` di tabel `properties` sudah terisi
- Periksa console browser untuk error JavaScript

### Error saat save budget
- Periksa validasi di `BudgetController::update()`
- Pastikan semua kategori aktif sudah ter-initialize

## 📚 Dokumentasi USALI

Untuk referensi lengkap tentang USALI (Uniform System of Accounts for the Lodging Industry), kunjungi:
- [AHLA - American Hotel & Lodging Association](https://www.ahla.com/)

## ✅ Testing Checklist

- [ ] Migrations berhasil dijalankan
- [ ] Seeder berhasil mengisi budget categories
- [ ] Bisa membuat budget period baru
- [ ] Bisa input budget drivers (Occupancy & ADR)
- [ ] Auto-calculate room revenue berfungsi
- [ ] Bisa simpan budget plans
- [ ] Bisa ubah status budget (Draft → Approved → Locked)
- [ ] Laporan P&L menampilkan data actual dengan benar
- [ ] Variance calculation akurat
- [ ] Bisa delete budget (hanya yang draft/approved)

## 🎉 Selesai!

Modul Budgeting Hotel sudah siap digunakan. Untuk pertanyaan atau issue, silakan hubungi tim development.

---
**Version:** 1.0.0
**Date:** December 5, 2025
**Author:** AI Assistant (Claude)
