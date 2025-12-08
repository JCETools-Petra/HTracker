# Budget Import Debug Guide

## Masalah yang Ditemukan & Diperbaiki

### 1. ✅ Bug Query YTD (FIXED)
**Lokasi:** `app/Services/FinancialReportService.php:45`

**Masalah:** Query YTD tidak mengambil `SUM(forecast_value)`, menyebabkan forecast YTD selalu 0.

**Perbaikan:** Menambahkan `SUM(forecast_value) as total_forecast` ke query.

```php
// SEBELUM (❌ SALAH)
->selectRaw('financial_category_id, SUM(actual_value) as total_actual, SUM(budget_value) as total_budget')

// SESUDAH (✅ BENAR)
->selectRaw('financial_category_id, SUM(actual_value) as total_actual, SUM(budget_value) as total_budget, SUM(forecast_value) as total_forecast')
```

### 2. ✅ Enhanced Logging (IMPROVED)
**Lokasi:** `app/Imports/BudgetTemplateImport.php:113-120`

**Perbaikan:** Menambahkan logging detail untuk setiap kategori yang diimport, termasuk:
- Nilai per bulan untuk semua 12 bulan
- Total tahunan
- Rata-rata bulanan

Ini akan membantu mengidentifikasi jika ada nilai yang salah saat import.

---

## Tools Debugging Baru

### Command 1: Verify Budget Data
Memeriksa integritas data budget dan mendeteksi masalah seperti:
- Missing months (kurang dari 12 bulan)
- Duplicate months
- Invalid month values

**Cara Penggunaan:**
```bash
# Verify semua kategori untuk property dan tahun tertentu
php artisan budget:verify {property_id} {year}

# Verify kategori spesifik
php artisan budget:verify {property_id} {year} --category_id={category_id}

# Contoh: Verify budget 2026 untuk property 1
php artisan budget:verify 1 2026
```

**Output:**
```
Verifying budget data for SUNNYDAY INN - Year 2026
================================================================================

Budget Statistics:
--------------------------------------------------------------------------------
+-----+--------------------------------+--------------+------------------+--------+
| ID  | Category                       | Avg Monthly  | Yearly Total     | Months |
+-----+--------------------------------+--------------+------------------+--------+
| 729 | SALARIES & WAGES               | 17,143,400.00| 205,720,800.00   | 12     |
| 730 | LEBARAN BONUS                  | 1,428,617.00 | 17,143,404.00    | 12     |
+-----+--------------------------------+--------------+------------------+--------+

✓ No issues found! Budget data is consistent.
```

### Command 2: Show Budget Details
Menampilkan detail budget per bulan untuk kategori tertentu.

**Cara Penggunaan:**
```bash
php artisan budget:show {property_id} {year} {category_id}

# Contoh: Lihat detail SALARIES & WAGES (ID 729) untuk tahun 2026
php artisan budget:show 1 2026 729
```

**Output:**
```
Budget Details:
Property: SUNNYDAY INN (ID: 1)
Category: SALARIES & WAGES (ID: 729)
Full Path: Front Office > PAYROLL & RELATED EXPENSES > SALARIES & WAGES
Year: 2026
====================================================================================================
+--------+----------+------------------+------------------+------------------+----------+
| Month# | Month    | Budget           | Actual           | Forecast         | Entry ID |
+--------+----------+------------------+------------------+------------------+----------+
| 1      | January  | 17,143,400.00    | 0.00             | 0.00             | 1001     |
| 2      | February | 17,143,400.00    | 0.00             | 0.00             | 1002     |
| 3      | March    | 17,143,400.00    | 0.00             | 0.00             | 1003     |
...
| 12     | December | 17,143,400.00    | 0.00             | 0.00             | 1012     |
+--------+----------+------------------+------------------+------------------+----------+
|        | TOTAL    | 205,720,800.00   | 0.00             | 0.00             |          |
|        | AVERAGE  | 17,143,400.00    | 0.00             | 0.00             |          |
+--------+----------+------------------+------------------+------------------+----------+

Summary:
  Total Months: 12
  Expected Total (if 12 months × average): 205,720,800.00
```

---

## Cara Debug Masalah Anda

### Langkah 1: Periksa Data Aktual di Database
```bash
# Ganti {property_id} dan {year} sesuai kasus Anda
php artisan budget:show 1 2026 729
```

Ini akan menampilkan:
- Berapa nilai budget per bulan yang SEBENARNYA tersimpan
- Total tahunan yang sebenarnya
- Apakah ada bulan yang hilang atau duplikat

### Langkah 2: Bandingkan dengan Yang Diharapkan
Jika Anda mengimport nilai **17,143,400 per bulan**, maka:
- **Expected Monthly:** 17,143,400
- **Expected Yearly Total:** 17,143,400 × 12 = 205,720,800

Jika output command menunjukkan nilai berbeda, berarti ada masalah saat import.

### Langkah 3: Cek Log Import
Setelah perbaikan ini, setiap kali Anda import Excel, sistem akan mencatat detail lengkap di:
```
storage/logs/laravel.log
```

Cari baris seperti:
```
[2025-12-08 10:00:00] local.INFO: Successfully imported 12 months for Category ID 729 (SALARIES & WAGES)
{
  "category_id": 729,
  "category_name": "SALARIES & WAGES",
  "monthly_values": {
    "january": 17143400,
    "february": 17143400,
    ...
  },
  "yearly_total": 205720800,
  "average_monthly": 17143400
}
```

Periksa apakah `yearly_total` sudah sesuai harapan.

---

## Analisis Masalah Anda

Berdasarkan data yang Anda berikan:
- **Input di Excel:** 17,143,400 per bulan
- **Expected Yearly Total:** 205,720,800
- **Actual di Web:** 231,435,904

**Perhitungan:**
```
Selisih: 231,435,904 - 205,720,800 = 25,715,104
Rasio: 231,435,904 ÷ 205,720,800 = 1.125 (atau 9/8)
Nilai per bulan yang tersimpan: 231,435,904 ÷ 12 = 19,286,325.33
```

**Kemungkinan Penyebab:**
1. **Data yang diimport berbeda dari yang ditampilkan di screenshot**
   - Solusi: Gunakan `php artisan budget:show` untuk melihat nilai sebenarnya

2. **Anda melihat parent category yang menjumlahkan beberapa children**
   - Solusi: Pastikan Anda melihat kategori yang benar (SALARIES & WAGES bukan Front Office)

3. **Ada proses manual input yang mengubah nilai setelah import**
   - Solusi: Jangan klik "Simpan Budget" di form manual setelah import Excel

4. **Import dijalankan lebih dari sekali dengan nilai berbeda**
   - Solusi: Hapus data lama dulu sebelum reimport

---

## Cara Memperbaiki Data yang Salah

### Option 1: Reimport Ulang
1. Hapus data budget untuk tahun tersebut (via database atau buat fitur hapus)
2. Import ulang dari Excel template yang benar
3. Verify dengan `php artisan budget:verify`

### Option 2: Edit Manual
1. Lihat detail dengan `php artisan budget:show`
2. Edit langsung via database atau form web
3. Pastikan semua 12 bulan punya nilai yang sama (jika memang seharusnya sama)

---

## Cara Mencegah Masalah di Masa Depan

1. **Gunakan Import Excel, BUKAN Manual Input** untuk budget tahunan
   - Import Excel: Input nilai PER BULAN (12 kolom)
   - Manual Input: Input nilai TAHUNAN (dibagi 12 otomatis)
   - JANGAN campur kedua metode!

2. **Selalu Verify Setelah Import**
   ```bash
   php artisan budget:verify {property_id} {year}
   ```

3. **Cek Log Setelah Import**
   - Buka `storage/logs/laravel.log`
   - Pastikan `yearly_total` sesuai harapan

4. **Backup Data Sebelum Import**
   - Export data lama ke Excel dulu
   - Baru import data baru

---

## Kontak

Jika masalah masih berlanjut setelah menggunakan tools di atas, silakan berikan output dari:
```bash
php artisan budget:show {property_id} {year} {category_id}
```

Dan screenshot dari:
1. Excel template yang diimport
2. Halaman web yang menampilkan nilai salah
3. Log import dari `storage/logs/laravel.log`
