# Hotel Budget Management Module V2 - Departmental Budget Tracker

**Modul Budgeting Hotel dengan Departmental Expense Tracking**

## 🎯 Konsep Baru

Sistem ini dirancang untuk tracking budget hotel yang lebih praktis dan sesuai kebutuhan operasional:

### **Budget Tahunan dengan Tracking Departmental**

```
Properti: Sunnyday Inn - Budget 2026
├─ Revenue Target Total:    Rp 1.200.000.000
├─ Expense Budget Total:     Rp 800.000.000
└─ Target Profit:            Rp 400.000.000

Departmental Allocation:
├─ Rooms Department:        Rp 200.000.000 (Tersisa: Rp 180.000.000)
├─ F&B Department:          Rp 300.000.000 (Tersisa: Rp 250.000.000)
├─ Marketing:               Rp 100.000.000 (Tersisa: Rp 95.000.000)
├─ Maintenance:             Rp 150.000.000 (Tersisa: Rp 140.000.000)
└─ Admin & General:         Rp 50.000.000  (Tersisa: Rp 45.000.000)

Budget Health: ⚠️ WARNING (Budget terpakai 70%)
Forecast: Budget habis di bulan Oktober 2026
```

## 📊 Fitur Utama

### 1. **Simple Budget Setup**
- Input 3 angka utama: Revenue Target, Expense Budget, Target Profit
- Alokasi budget per department (Rooms, F&B, Marketing, dll)
- Setup cepat dalam 5 menit

### 2. **Manual Expense Tracking**
- Input transaksi pengeluaran per department
- Upload receipt/bukti pembayaran
- Kategorisasi expense (Supplies, Payroll, Utilities, dll)
- Auto-deduct dari budget allocation

### 3. **Budget Monitoring Dashboard**
- Real-time budget usage tracking
- Department-level breakdownBudget health indicators (🟢 Healthy / ⚠️ Warning / 🔴 Critical)
- Monthly expense trends
- Revenue target vs actual comparison

### 4. **Burn Rate Forecasting**
- Prediksi kapan budget akan habis
- Average monthly spending calculation
- Early warning system

### 5. **Approval Workflow**
```
Draft → Submitted → Approved → Locked
```
- Property submit budget untuk approval
- Admin/Owner approve atau reject
- Budget approved bisa di-lock (final, tidak bisa diubah)

### 6. **Revenue Target Tracking**
- Set monthly revenue targets (distribusi dari target tahunan)
- Auto-compare dengan data actual dari `daily_incomes`
- Variance analysis (Target vs Actual)

## 🗂️ Struktur Database

### Tabel Utama

**1. `budget_periods`** - Periode Budget Tahunan
```sql
- property_id
- year
- total_revenue_target
- total_expense_budget
- target_profit
- status (draft/submitted/approved/locked)
- submitted_at, approved_at, approved_by
```

**2. `budget_departments`** - Alokasi Budget per Department
```sql
- budget_period_id
- name (Rooms, F&B, Marketing, etc)
- code (RMS, FNB, MKT, etc)
- allocated_budget
- sort_order
```

**3. `budget_expenses`** - Transaksi Pengeluaran
```sql
- budget_department_id
- expense_date
- description
- amount
- category
- receipt_number, receipt_file
- notes
- created_by
```

**4. `budget_revenue_targets`** - Target Revenue Bulanan
```sql
- budget_period_id
- month (1-12)
- target_amount
```

## 🚀 Cara Penggunaan

### Step 1: Buat Budget Tahunan Baru

1. Pilih properti
2. Akses `/admin/properties/{property_id}/budgets`
3. Klik "Budget Baru"
4. Isi form:
   - Tahun budget (2026)
   - Target Revenue Total: Rp 1.200.000.000
   - Budget Expense Total: Rp 800.000.000
   - Profit otomatis terhitung: Rp 400.000.000

5. Alokasikan budget per department:
   ```
   Department          Code    Budget Allocation
   ---------------------------------------------
   Rooms Department    RMS     Rp 200.000.000
   F&B Department      FNB     Rp 300.000.000
   Marketing           MKT     Rp 100.000.000
   Maintenance         MNT     Rp 150.000.000
   Admin & General     ADM     Rp 50.000.000
   ```

6. Klik "Create Budget"

### Step 2: Input Expense Transactions

1. Dari dashboard budget, klik "Input Expense"
2. Isi form transaksi:
   - Pilih Department: Rooms Department
   - Tanggal: 15 Januari 2026
   - Deskripsi: Pembelian Guest Amenities
   - Jumlah: Rp 5.000.000
   - Kategori: Supplies
   - Upload receipt (optional)
   - Notes (optional)

3. Klik "Save"

**Budget akan otomatis berkurang:**
```
Rooms Department:
- Allocated: Rp 200.000.000
- Used: Rp 5.000.000
- Remaining: Rp 195.000.000 ✅
```

### Step 3: Monitoring Budget

Dashboard akan menampilkan:

**Summary Cards:**
- 💰 Total Budget Allocated: Rp 800.000.000
- 📉 Total Used: Rp 560.000.000
- 💵 Remaining: Rp 240.000.000
- 📊 Usage: 70% ⚠️ WARNING

**Forecast:**
- Average Monthly Spending: Rp 80.000.000
- Months Remaining: 3 bulan
- **Predicted Depletion: Oktober 2026**

**Department Breakdown:**
```
Department       Allocated    Used         Remaining    Status
-----------------------------------------------------------------
Rooms            200M        150M (75%)   50M          ⚠️ Warning
F&B              300M        200M (67%)   100M         🟢 Healthy
Marketing        100M        80M  (80%)   20M          🔴 Critical
Maintenance      150M        100M (67%)   50M          🟢 Healthy
Admin            50M         30M  (60%)   20M          🟢 Healthy
```

**Monthly Trend Chart:**
```
Jan: 60M | Feb: 70M | Mar: 80M | Apr: 90M ↗️ Spending naik!
```

### Step 4: Submit untuk Approval

1. Klik "Submit for Approval"
2. Status berubah: Draft → Submitted
3. Admin/Owner menerima notifikasi (future feature)

### Step 5: Approval (Admin/Owner)

Admin bisa:
- **Approve**: Budget disetujui (status: Approved)
- **Reject**: Kembalikan ke Draft dengan notes perbaikan
- **Lock**: Final lock, tidak bisa diubah lagi

## 🔧 Installation Guide

### 1. Run Migrations

```bash
php artisan migrate
```

Migrations akan membuat 4 tabel baru:
- `budget_periods`
- `budget_departments`
- `budget_expenses`
- `budget_revenue_targets`

### 2. Setup Storage untuk Receipts

```bash
php artisan storage:link
```

Ini untuk menyimpan file receipt yang diupload.

### 3. Akses Module

```
/admin/properties/{property_id}/budgets
```

Atau tambahkan link di property show page.

## 📝 API Routes

```php
// List budget periods
GET /admin/properties/{property}/budgets

// Create form
GET /admin/properties/{property}/budgets/create

// Store new budget
POST /admin/properties/{property}/budgets

// Dashboard monitoring
GET /admin/properties/{property}/budgets/{budgetPeriod}

// Update budget allocations
PUT /admin/properties/{property}/budgets/{budgetPeriod}

// Delete budget
DELETE /admin/properties/{property}/budgets/{budgetPeriod}

// === Expense Management ===

// Expense input form
GET /admin/properties/{property}/budgets/{budgetPeriod}/expenses/create

// Store expense transaction
POST /admin/properties/{property}/budgets/{budgetPeriod}/expenses

// Delete expense
DELETE /admin/properties/{property}/budgets/{budgetPeriod}/expenses/{expense}

// === Approval Workflow ===

// Submit budget
POST /admin/properties/{property}/budgets/{budgetPeriod}/submit

// Approve budget
POST /admin/properties/{property}/budgets/{budgetPeriod}/approve

// Reject budget
POST /admin/properties/{property}/budgets/{budgetPeriod}/reject

// Lock budget (final)
POST /admin/properties/{property}/budgets/{budgetPeriod}/lock
```

## 💡 Use Cases

### Use Case 1: Monthly Budget Review

GM ingin review budget setiap akhir bulan:

1. Akses dashboard budget
2. Lihat summary: Budget terpakai 70%, sisa 30%
3. Cek department mana yang overspending (Marketing 80% 🔴)
4. Review expense transactions di Marketing department
5. Diskusikan dengan Marketing Manager untuk efisiensi
6. Adjust allocation jika perlu (selama belum locked)

### Use Case 2: Expense Approval

Staff F&B mau beli bahan makanan:

1. Staff input expense transaction:
   - Department: F&B
   - Description: Pembelian daging dan sayuran
   - Amount: Rp 15.000.000
   - Upload receipt

2. Manager F&B review di dashboard
3. Budget F&B otomatis berkurang Rp 15M
4. Jika mendekati limit (>85%), sistem warning

### Use Case 3: Budget Planning Next Year

Owner mau planning budget 2027 berdasarkan data 2026:

1. Review actual spending 2026 dari dashboard
2. Lihat department mana yang sering overspending
3. Adjust allocation untuk 2027:
   - Marketing: Naikkan dari 100M → 150M (sering overspending)
   - Maintenance: Turunkan dari 150M → 120M (underspending)

4. Set revenue target lebih realistis berdasarkan achievement 2026

## 🎨 Customization

### Menambah Department Baru

Edit form create budget, tambahkan department:

```javascript
const defaultDepartments = [
    { name: 'Rooms Department', code: 'RMS', budget: 0 },
    { name: 'F&B Department', code: 'FNB', budget: 0 },
    { name: 'Marketing', code: 'MKT', budget: 0 },
    { name: 'Maintenance', code: 'MNT', budget: 0 },
    { name: 'Admin & General', code: 'ADM', budget: 0 },
    { name: 'Spa & Wellness', code: 'SPA', budget: 0 }, // NEW!
];
```

### Menambah Expense Category

Edit dropdown category di expense form:

```php
$categories = [
    'Supplies',
    'Payroll',
    'Utilities',
    'Marketing',
    'Maintenance',
    'Training',      // NEW!
    'Equipment',     // NEW!
    'Miscellaneous',
];
```

## 🔐 Authorization

- **Property Users**: Bisa create budget, input expense, submit untuk approval
- **Admin/Owner**: Bisa approve/reject budget, lock budget, full access
- **Budget Locked**: Tidak ada yang bisa edit (read-only)

## 📈 Best Practices

1. **Setup budget di awal tahun**: Januari untuk budget tahun berjalan
2. **Input expense real-time**: Jangan tunggu akhir bulan
3. **Review weekly**: Cek dashboard setiap minggu
4. **Lock budget setelah year-end**: Proteksi data historis
5. **Use receipt upload**: Dokumentasi penting untuk audit

## 🐛 Troubleshooting

### Budget tidak berkurang setelah input expense
- Pastikan expense sudah tersimpan (cek tabel `budget_expenses`)
- Refresh halaman dashboard
- Periksa relasi `budget_department_id` valid

### Forecast tidak akurat
- Forecast berdasarkan historical spending YTD
- Minimal 1 bulan data untuk forecast
- Jika baru setup, forecast akan muncul setelah ada transaksi

### Tidak bisa edit budget
- Cek status budget: Locked tidak bisa diedit
- Cek authorization: Hanya admin/owner yang bisa approve/lock

## 📚 Model Attributes & Methods

### BudgetPeriod

**Computed Attributes:**
- `total_expense_used` - Total pengeluaran yang sudah dipakai
- `remaining_expense_budget` - Sisa budget
- `budget_used_percentage` - Persentase budget terpakai
- `total_revenue_actual` - Revenue actual dari daily_incomes
- `forecasted_depletion_month` - Prediksi bulan habis budget
- `budget_health` - Status: healthy/warning/critical

**Methods:**
- `isDraft()`, `isSubmitted()`, `isApproved()`, `isLocked()`

### BudgetDepartment

**Computed Attributes:**
- `total_used` - Total pengeluaran department
- `remaining_budget` - Sisa budget department
- `used_percentage` - Persentase terpakai
- `health_status` - Status kesehatan budget

### BudgetExpense

**Scopes:**
- `inDateRange($start, $end)` - Filter by date range
- `inMonth($year, $month)` - Filter by month
- `ofCategory($category)` - Filter by category

## ✅ Testing Checklist

- [ ] Migration berhasil dijalankan
- [ ] Bisa buat budget period baru
- [ ] Bisa alokasi budget per department
- [ ] Bisa input expense transaction
- [ ] Bisa upload receipt file
- [ ] Budget otomatis berkurang setelah expense
- [ ] Dashboard menampilkan summary yang benar
- [ ] Forecast calculation akurat
- [ ] Submit workflow berfungsi
- [ ] Approve/Reject workflow berfungsi
- [ ] Lock budget mencegah edit
- [ ] Revenue target comparison akurat
- [ ] Bisa delete budget (yang belum locked)
- [ ] Bisa delete expense transaction

## 🎉 Selesai!

Modul budgeting hotel Anda sudah siap digunakan!

**Next Steps:**
1. Run migrations
2. Buat budget pertama
3. Input beberapa expense transactions untuk testing
4. Review dashboard dan forecast

**Butuh bantuan?**
- Cek controller: `app/Http/Controllers/Admin/BudgetController.php`
- Cek models: `app/Models/Budget*.php`
- Cek views: `resources/views/admin/budgets/`

---
**Version:** 2.0.0
**Date:** December 5, 2025
**Type:** Departmental Budget with Expense Tracking
