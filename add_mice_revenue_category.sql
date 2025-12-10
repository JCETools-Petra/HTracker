-- ============================================================
-- SQL Script untuk Menambahkan Kategori MICE Revenue
-- ke Laporan Budget & P&L
-- ============================================================
--
-- Jalankan script ini di phpMyAdmin untuk menambahkan kategori
-- MICE Revenue ke semua properti yang ada di sistem.
--
-- ============================================================

-- Insert MICE Revenue category untuk setiap properti
INSERT INTO financial_categories (property_id, parent_id, name, code, type, is_payroll, sort_order, created_at, updated_at)
SELECT
    p.id as property_id,
    NULL as parent_id,
    'MICE Revenue' as name,
    'MICE_REV' as code,
    'revenue' as type,
    0 as is_payroll,
    3 as sort_order,
    NOW() as created_at,
    NOW() as updated_at
FROM properties p
WHERE NOT EXISTS (
    SELECT 1
    FROM financial_categories fc
    WHERE fc.property_id = p.id
    AND fc.code = 'MICE_REV'
);

-- Verifikasi hasil insert
SELECT
    fc.id,
    p.name as property_name,
    fc.name as category_name,
    fc.code,
    fc.type,
    fc.sort_order
FROM financial_categories fc
JOIN properties p ON fc.property_id = p.id
WHERE fc.code = 'MICE_REV'
ORDER BY p.name;

-- ============================================================
-- Setelah menjalankan script ini:
-- 1. Refresh halaman P&L Report di browser
-- 2. MICE Revenue akan muncul di:
--    - Revenue Breakdown Chart
--    - Total Revenue
--    - P&L Table
-- ============================================================
