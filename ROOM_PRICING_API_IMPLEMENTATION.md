# Implementasi Room Pricing API

## Ringkasan

Implementasi ini menambahkan API publik yang aman untuk mengakses informasi harga kamar dan tipe kamar dari setiap properti. API ini dapat digunakan oleh website eksternal untuk menampilkan harga real-time dengan sistem keamanan berbasis API key.

## Fitur yang Diimplementasikan

### 1. **API Key Authentication System**
- Setiap properti dapat memiliki multiple API keys
- API key format: `htk_` + 48 karakter random (total 52 karakter)
- Mendukung origin restriction (CORS)
- Status active/inactive
- Tracking last used timestamp

### 2. **Secure API Endpoints**
- `GET /api/properties/{property}/room-pricing` - Mendapatkan semua tipe kamar dan harga
- `GET /api/properties/{property}/room-pricing/{roomTypeId}` - Mendapatkan harga spesifik tipe kamar

### 3. **Dynamic Pricing Integration**
- Menggunakan sistem BAR (Best Available Rate) yang sudah ada
- Harga berubah otomatis berdasarkan okupansi
- Menampilkan informasi lengkap: bottom rate, current price, pricing rule

### 4. **Admin Management Interface**
Routes untuk mengelola API keys:
- Lihat daftar API keys
- Buat API key baru
- Edit API key (nama, allowed origins)
- Toggle active/inactive
- Hapus API key

### 5. **Komprehensif Documentation**
- API documentation lengkap dengan contoh penggunaan
- Contoh implementasi dalam berbagai bahasa (JavaScript, PHP, Python)
- Penjelasan dynamic pricing system
- Error handling guide

## File yang Dibuat/Dimodifikasi

### Database
- `database/migrations/2025_12_10_000000_create_api_keys_table.php` - Migration untuk tabel api_keys

### Models
- `app/Models/ApiKey.php` - Model untuk API keys
- `app/Models/Property.php` - Ditambahkan relasi `apiKeys()`

### Controllers
- `app/Http/Controllers/Api/RoomPricingController.php` - Controller untuk API endpoints
- `app/Http/Controllers/Admin/ApiKeyController.php` - Controller untuk manajemen API keys

### Middleware
- `app/Http/Middleware/AuthenticateApiKey.php` - Middleware untuk autentikasi API key
- `bootstrap/app.php` - Register middleware alias `api.key`

### Routes
- `routes/api.php` - API routes (dibuat baru)
- `routes/web.php` - Ditambahkan routes untuk manajemen API keys
- `bootstrap/app.php` - Register API routes

### Documentation
- `API_DOCUMENTATION.md` - Dokumentasi lengkap penggunaan API
- `ROOM_PRICING_API_IMPLEMENTATION.md` - File ini (summary implementasi)

## Cara Menggunakan

### Setup (Setelah Pull)

1. **Jalankan Migration**
   ```bash
   php artisan migrate
   ```

2. **Generate API Key untuk Property**
   - Login sebagai admin/owner
   - Pilih property
   - Akses: `/admin/properties/{property_id}/api-keys`
   - Klik "Buat API Key Baru"
   - Isi nama dan allowed origins (opsional)
   - Simpan API key yang ditampilkan

3. **Test API**
   ```bash
   curl -H "X-API-Key: htk_your_api_key_here" \
     https://your-domain.com/api/properties/1/room-pricing
   ```

### Integrasi dengan Website Eksternal

Lihat `API_DOCUMENTATION.md` untuk contoh lengkap integrasi dengan:
- JavaScript (Fetch API, jQuery)
- PHP (cURL)
- Python (requests)

## Keamanan

### 1. **API Key Authentication**
- Setiap request harus menyertakan valid API key
- API key bisa dikirim via header `X-API-Key` atau query parameter `api_key`
- Header lebih disarankan untuk keamanan

### 2. **Origin Restriction**
- Admin dapat membatasi API key hanya untuk domain tertentu
- Mendukung wildcard subdomain (*.example.com)
- Jika tidak diisi, semua origin diizinkan

### 3. **Property Authorization**
- API key hanya bisa akses data dari property yang sesuai
- Validasi property_id vs api_key.property_id

### 4. **CORS Support**
- Automatic CORS headers untuk allowed origins
- Mendukung preflight requests (OPTIONS)

### 5. **Activity Tracking**
- Timestamp `last_used_at` diupdate setiap kali API key digunakan
- Bisa digunakan untuk monitoring dan audit

## Response Data

API mengembalikan informasi lengkap:

### Property Info
- Nama, alamat, telepon
- Total rooms

### Occupancy Info
- Tanggal (hari ini)
- Occupied rooms, available rooms
- Occupancy percentage

### Pricing Info
- Active BAR level (1-5)
- BAR thresholds
- Penjelasan pricing yang sedang aktif

### Room Types
- Semua tipe kamar di property
- Bottom rate (harga dasar)
- **Current price** (harga aktif real-time berdasarkan okupansi)
- Pricing rules (jika ada)

## Contoh Response

```json
{
  "success": true,
  "data": {
    "property": {
      "id": 1,
      "name": "Hotel Example",
      "total_rooms": 100
    },
    "occupancy": {
      "occupied_rooms": 65,
      "occupancy_percentage": 65.00
    },
    "pricing": {
      "active_bar_level": 3
    },
    "room_types": [
      {
        "id": 1,
        "name": "Deluxe Room",
        "bottom_rate": 500000,
        "current_price": 605000
      }
    ]
  }
}
```

## Admin Routes

Semua routes dibawah prefix `/admin/properties/{property}/api-keys`:

| Method | Route | Action |
|--------|-------|--------|
| GET | `/` | List semua API keys |
| GET | `/create` | Form buat API key baru |
| POST | `/` | Simpan API key baru |
| GET | `/{apiKey}` | Lihat detail API key |
| GET | `/{apiKey}/edit` | Form edit API key |
| PUT | `/{apiKey}` | Update API key |
| DELETE | `/{apiKey}` | Hapus API key |
| POST | `/{apiKey}/toggle` | Toggle active/inactive |

**Authorization:** Admin, Owner, Pengurus (untuk property mereka sendiri)

## Technical Details

### API Key Generation
- Format: `htk_` + 48 random characters
- Menggunakan `Illuminate\Support\Str::random(48)`
- Unique check sebelum menyimpan

### Dynamic Pricing Calculation
Menggunakan trait `CalculatesBarPrices`:
1. Get occupancy hari ini
2. Tentukan BAR level berdasarkan threshold
3. Calculate price dengan formula: `bottom_rate × (1 + percentage_increase/100)^(level - starting_bar)`

### Middleware Flow
1. Extract API key dari header atau query parameter
2. Validasi API key exists dan is_active
3. Check origin restrictions (jika ada)
4. Attach API key model ke request
5. Record usage (async, after response)

## Next Steps (Opsional)

Untuk development lebih lanjut, bisa ditambahkan:

1. **Views untuk Admin Management**
   - Buat views di `resources/views/admin/properties/api-keys/`
   - Form create/edit API key
   - List API keys dengan status

2. **Rate Limiting**
   - Tambahkan throttle middleware
   - Limit requests per menit/jam

3. **Webhook Notifications**
   - Notifikasi saat ada perubahan harga
   - Webhook saat occupancy berubah

4. **API Analytics**
   - Dashboard untuk melihat API usage
   - Statistics per API key
   - Popular endpoints

5. **API Versioning**
   - Support multiple API versions
   - Deprecation warnings

6. **Additional Endpoints**
   - `/api/properties/{property}/availability` - Check room availability
   - `/api/properties/{property}/reservations` - Create reservation via API

## Testing

### Manual Testing

```bash
# Test dengan valid API key
curl -H "X-API-Key: htk_xxx" http://localhost/api/properties/1/room-pricing

# Test dengan invalid API key
curl -H "X-API-Key: invalid" http://localhost/api/properties/1/room-pricing

# Test dengan query parameter
curl "http://localhost/api/properties/1/room-pricing?api_key=htk_xxx"

# Test specific room type
curl -H "X-API-Key: htk_xxx" http://localhost/api/properties/1/room-pricing/1
```

### Unit Testing (Future)

Bisa dibuat test untuk:
- API key generation uniqueness
- Origin validation logic
- Pricing calculation accuracy
- Authorization checks

## Troubleshooting

### API Key tidak bekerja
- Pastikan API key is_active = true
- Check origin restrictions
- Verify property_id match

### CORS errors
- Tambahkan origin ke allowed_origins
- Pastikan format origin benar (dengan https://)

### Harga tidak update
- Check daily_occupancies table ada data hari ini
- Verify pricing_rules configured correctly
- Check BAR thresholds di property

## Maintainer Notes

- API key tidak pernah di-expose di list view (hidden field)
- Hanya ditampilkan sekali setelah creation
- Soft delete tidak diimplementasikan (direct delete)
- Last used timestamp diupdate async untuk performance

---

**Implementasi oleh:** Claude
**Tanggal:** 2025-12-10
**Version:** 1.0
