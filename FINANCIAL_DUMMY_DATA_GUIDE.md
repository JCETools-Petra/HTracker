# Financial Dummy Data Seeder - User Guide

## Overview

Seeder ini membuat data dummy yang realistis untuk sistem budgeting dengan mempertimbangkan pola seasonal hotel (high season dan low season).

## Seasonal Pattern (Pola Musiman)

Data dummy menggunakan pola okupansi hotel yang realistis untuk Indonesia:

### 🔥 HIGH SEASON (Musim Ramai)
- **Desember**: 145% dari baseline - Peak season (Natal & Tahun Baru)
- **Januari**: 135% dari baseline - Liburan tahun baru
- **Juni**: 125% dari baseline - Awal liburan sekolah
- **Juli**: 130% dari baseline - Puncak liburan sekolah

### 🌤️ SHOULDER SEASON (Musim Sedang)
- **Mei**: 95% dari baseline - Sebelum liburan
- **Agustus**: 105% dari baseline - Akhir liburan
- **November**: 85% dari baseline - Sebelum peak season

### ❄️ LOW SEASON (Musim Sepi)
- **Februari**: 65% dari baseline - Paling sepi (setelah liburan)
- **Maret**: 70% dari baseline - Masih sepi
- **April**: 85% dari baseline - Mulai recovery
- **September**: 75% dari baseline - Sepi lagi
- **Oktober**: 80% dari baseline - Mulai recovery

## Data yang Di-generate

### 1. Budget Data
- **Periode**: 2024 dan 2025
- **Distribusi**: Merata per bulan (Annual Budget / 12)
- **Semua Kategori**: Semua kategori expense mendapat budget

### 2. Actual Data
- **Periode**: 2024 (12 bulan penuh)
- **Variasi Seasonal**: Sesuai pola di atas
- **Random Variance**: ±10% untuk realism
- **Formula**: `Actual = (Budget/12) × SeasonalMultiplier × (1 ± 10%)`

## Contoh Budget Realistis (Per Tahun)

### Payroll (Gaji & Tunjangan)
- **Front Office**: Rp 180-240 juta/tahun (15-20 jt/bulan)
- **Housekeeping**: Rp 240-360 juta/tahun (20-30 jt/bulan) - Staff terbanyak
- **F&B Product**: Rp 150-210 juta/tahun (12.5-17.5 jt/bulan)
- **F&B Service**: Rp 180-240 juta/tahun (15-20 jt/bulan)
- **POMAC**: Rp 120-180 juta/tahun (10-15 jt/bulan)
- **Accounting**: Rp 96-144 juta/tahun (8-12 jt/bulan)

### Service Charge
- Rp 60-120 juta/tahun (5-10 jt/bulan)

### Employee Benefits / BPJS
- Rp 24-48 juta/tahun (2-4 jt/bulan)

### Cost of Goods Sold (COGS)
- **Food Cost**: Rp 180-300 juta/tahun (15-25 jt/bulan)
- **Beverage Cost**: Rp 72-144 juta/tahun (6-12 jt/bulan)

### Energy Costs
- **Electricity (PLN)**: Rp 120-180 juta/tahun (10-15 jt/bulan)
- **Water (PDAM)**: Rp 36-60 juta/tahun (3-5 jt/bulan)
- **Fuel/Diesel (Genset)**: Rp 24-48 juta/tahun (2-4 jt/bulan)

### Supplies
- **Cleaning Supplies**: Rp 36-72 juta/tahun (3-6 jt/bulan)
- **Guest Amenities**: Rp 48-96 juta/tahun (4-8 jt/bulan)
- **Linen & Towels**: Rp 60-120 juta/tahun (5-10 jt/bulan)
- **Laundry**: Rp 36-72 juta/tahun (3-6 jt/bulan)

### Maintenance
- **Building Repairs**: Rp 48-120 juta/tahun (4-10 jt/bulan)
- **Electrical & Mechanical**: Rp 24-72 juta/tahun (2-6 jt/bulan)

### Other Operating Expenses
- **Telecommunications**: Rp 12-24 juta/tahun (1-2 jt/bulan)
- **Printing & Stationery**: Rp 6-12 juta/tahun (500rb-1jt/bulan)
- **Uniforms**: Rp 12-24 juta/tahun (1-2 jt/bulan)
- **Decorations**: Rp 12-24 juta/tahun (1-2 jt/bulan)
- **Transportation**: Rp 18-36 juta/tahun (1.5-3 jt/bulan)
- **Professional Fees**: Rp 24-60 juta/tahun (2-5 jt/bulan)
- **Bank Charges**: Rp 3.6-12 juta/tahun (300rb-1jt/bulan)

## Cara Menggunakan

### Step 1: Pastikan Migration dan Category Seeder Sudah Dijalankan

```bash
# Jalankan migration terlebih dahulu
php artisan migrate

# Seed kategori keuangan
php artisan db:seed --class=FinancialCategorySeeder
```

### Step 2: Jalankan Dummy Data Seeder

```bash
php artisan db:seed --class=FinancialDummyDataSeeder
```

Output yang diharapkan:
```
Seeding financial data for: Hotel ABC
Seeding financial data for: Hotel XYZ
Financial dummy data seeded successfully with seasonal variations!
```

### Step 3: Verifikasi Data

1. Login sebagai **admin** atau **property user**
2. Navigate ke **Financial > Laporan P&L**
3. Pilih tahun **2024** dan bulan **Desember** (high season)
4. Cek nilai actual lebih tinggi dari bulan **Februari** (low season)

## Kapan Menggunakan Seeder Ini?

✅ **Development & Testing**
- Saat develop fitur baru yang butuh data financial
- Testing seasonal reports
- Demo ke client

✅ **Training & Demo**
- Melatih staff cara menggunakan sistem
- Presentasi fitur ke stakeholder
- User acceptance testing (UAT)

❌ **JANGAN** digunakan di:
- Production environment dengan data real
- Database yang sudah ada data real customer

## Reset Data Dummy

Jika ingin reset dan re-seed:

```bash
# Hapus semua financial entries
php artisan tinker
>>> App\Models\FinancialEntry::truncate();
>>> exit

# Re-seed
php artisan db:seed --class=FinancialDummyDataSeeder
```

## Customization

Untuk memodifikasi pola seasonal atau budget amounts, edit file:
`database/seeders/FinancialDummyDataSeeder.php`

### Ubah Pola Seasonal
Edit method `getSeasonalMultiplier()`:
```php
private function getSeasonalMultiplier(int $month): float
{
    return match($month) {
        12 => 1.45, // Ubah nilai ini untuk Desember
        // ... dst
    };
}
```

### Ubah Budget Amounts
Edit method `getAnnualBudget()`:
```php
if (str_contains($categoryName, 'salaries')) {
    return rand(180000000, 240000000); // Ubah range di sini
}
```

## Contoh Pola Data yang Dihasilkan

### Contoh: Cleaning Supplies (HK)
**Annual Budget**: Rp 60,000,000

| Bulan | Budget | Seasonal | Actual | Variance |
|-------|--------|----------|--------|----------|
| Jan | 5,000,000 | 135% | 6,750,000 | +35% |
| Feb | 5,000,000 | 65% | 3,250,000 | -35% |
| Jun | 5,000,000 | 125% | 6,250,000 | +25% |
| Jul | 5,000,000 | 130% | 6,500,000 | +30% |
| Dec | 5,000,000 | 145% | 7,250,000 | +45% |

**Note**: Actual values juga ada random variance ±10% untuk lebih realistis.

## Troubleshooting

### Error: "No categories found"
**Solusi**: Jalankan `FinancialCategorySeeder` terlebih dahulu
```bash
php artisan db:seed --class=FinancialCategorySeeder
```

### Data tidak muncul di report
**Solusi**:
1. Clear cache: `php artisan cache:clear`
2. Pastikan properti punya kategori: Check di database
3. Cek year & month yang dipilih sesuai dengan data yang di-seed (2024)

### Budget terlalu besar/kecil
**Solusi**: Edit method `getAnnualBudget()` di seeder dan re-seed

## Tips & Best Practices

1. **Jalankan seeder di development environment saja**
2. **Backup database sebelum seeding** jika sudah ada data
3. **Review hasil seeder** di report untuk memastikan nilai masuk akal
4. **Customize seasonal pattern** sesuai dengan lokasi hotel Anda
   - Hotel di Bali: High season berbeda dengan Jakarta
   - Resort pegunungan: Pola seasonal berbeda
5. **Gunakan data ini sebagai template** untuk input data real nantinya

## Support

Untuk pertanyaan atau issue terkait seeder ini, silakan:
- Check file seeder: `database/seeders/FinancialDummyDataSeeder.php`
- Review dokumentasi utama: `FINANCIAL_BUDGETING_GUIDE.md`
- Contact development team

---

**Version**: 1.0
**Last Updated**: December 2025
**Compatible with**: Financial Budgeting System v1.0
