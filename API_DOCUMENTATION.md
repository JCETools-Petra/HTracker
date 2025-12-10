# Room Pricing API Documentation

API untuk mengakses informasi harga kamar dan tipe kamar dari properti HTracker. API ini dapat digunakan oleh website eksternal untuk menampilkan informasi harga real-time.

## Fitur Keamanan

1. **API Key Authentication** - Setiap request harus menyertakan API key yang valid
2. **Origin Restriction** - API key dapat dibatasi hanya untuk domain tertentu
3. **Rate Limiting** - Mencegah penyalahgunaan API
4. **CORS Support** - Mendukung cross-origin requests dari domain yang diizinkan

## Cara Menggunakan

### 1. Generate API Key

Admin atau owner properti dapat men-generate API key melalui dashboard:

1. Login ke dashboard admin
2. Pilih properti yang ingin dibuat API key-nya
3. Navigasi ke **Admin > Properties > [Pilih Property] > API Keys**
4. Klik "Buat API Key Baru"
5. Isi informasi:
   - **Name**: Nama untuk API key (contoh: "Website Booking")
   - **Allowed Origins**: Domain yang diizinkan mengakses API (opsional)
     - Kosongkan untuk mengizinkan semua domain
     - Contoh: `https://booking.example.com`
     - Gunakan wildcard untuk subdomain: `*.example.com`
     - Pisahkan dengan koma untuk multiple domains: `https://site1.com, https://site2.com`
6. Simpan API key dengan aman - hanya ditampilkan sekali!

### 2. Menggunakan API

#### Base URL

```
https://your-domain.com/api
```

#### Authentication

Sertakan API key di setiap request menggunakan salah satu cara berikut:

**Header (Recommended):**
```
X-API-Key: htk_your_api_key_here
```

**Query Parameter:**
```
?api_key=htk_your_api_key_here
```

## Endpoints

### 1. Get All Room Types and Pricing

Mendapatkan semua tipe kamar dan harga aktif untuk sebuah properti.

**Endpoint:**
```
GET /api/properties/{property_id}/room-pricing
```

**Headers:**
```
X-API-Key: htk_your_api_key_here
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "property": {
      "id": 1,
      "name": "Hotel Example",
      "address": "Jl. Contoh No. 123, Jakarta",
      "phone_number": "021-12345678",
      "total_rooms": 100
    },
    "occupancy": {
      "date": "2025-12-10",
      "occupied_rooms": 65,
      "available_rooms": 35,
      "occupancy_percentage": 65.00
    },
    "pricing": {
      "active_bar_level": 3,
      "active_bar_name": "bar_3",
      "bar_thresholds": {
        "bar_1": 20,
        "bar_2": 40,
        "bar_3": 60,
        "bar_4": 80,
        "bar_5": 90
      }
    },
    "room_types": [
      {
        "id": 1,
        "name": "Deluxe Room",
        "type": "hotel",
        "bottom_rate": 500000,
        "current_price": 605000,
        "pricing_rule": {
          "publish_rate": 750000,
          "starting_bar": 1,
          "percentage_increase": 10
        }
      },
      {
        "id": 2,
        "name": "Suite Room",
        "type": "hotel",
        "bottom_rate": 1000000,
        "current_price": 1210000,
        "pricing_rule": {
          "publish_rate": 1500000,
          "starting_bar": 1,
          "percentage_increase": 10
        }
      },
      {
        "id": 3,
        "name": "Meeting Room A",
        "type": "mice",
        "bottom_rate": 2000000,
        "current_price": 2000000,
        "pricing_rule": null
      }
    ]
  },
  "timestamp": "2025-12-10T08:30:00+07:00"
}
```

### 2. Get Specific Room Type Pricing

Mendapatkan harga untuk tipe kamar tertentu.

**Endpoint:**
```
GET /api/properties/{property_id}/room-pricing/{room_type_id}
```

**Headers:**
```
X-API-Key: htk_your_api_key_here
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "room_type": {
      "id": 1,
      "name": "Deluxe Room",
      "type": "hotel",
      "bottom_rate": 500000,
      "current_price": 605000,
      "pricing_rule": {
        "publish_rate": 750000,
        "starting_bar": 1,
        "percentage_increase": 10
      }
    },
    "occupancy": {
      "date": "2025-12-10",
      "occupied_rooms": 65,
      "active_bar_level": 3
    }
  },
  "timestamp": "2025-12-10T08:30:00+07:00"
}
```

## Response Fields Explanation

### Property Object
- `id`: ID properti
- `name`: Nama properti
- `address`: Alamat properti
- `phone_number`: Nomor telepon
- `total_rooms`: Total jumlah kamar

### Occupancy Object
- `date`: Tanggal data okupansi (hari ini)
- `occupied_rooms`: Jumlah kamar terisi
- `available_rooms`: Jumlah kamar tersedia
- `occupancy_percentage`: Persentase okupansi

### Pricing Object
- `active_bar_level`: Level BAR aktif (1-5) berdasarkan okupansi
- `active_bar_name`: Nama BAR aktif (bar_1 sampai bar_5)
- `bar_thresholds`: Ambang batas untuk setiap level BAR

### Room Type Object
- `id`: ID tipe kamar
- `name`: Nama tipe kamar
- `type`: Jenis kamar (`hotel` atau `mice`)
- `bottom_rate`: Harga dasar
- `current_price`: **Harga aktif saat ini** (sudah termasuk perhitungan BAR)
- `pricing_rule`: Aturan harga dinamis (null jika tidak ada)
  - `publish_rate`: Harga publish/rack rate
  - `starting_bar`: Level BAR mulai kenaikan harga
  - `percentage_increase`: Persentase kenaikan per level BAR

## Dynamic Pricing (BAR System)

Sistem pricing menggunakan BAR (Best Available Rate) yang dinamis berdasarkan okupansi:

1. **BAR Level** ditentukan oleh jumlah kamar terisi dibandingkan threshold
2. **Harga** naik secara progresif sesuai level BAR
3. **Formula**: `price = bottom_rate × (1 + percentage_increase/100)^(activeBarLevel - starting_bar)`

**Contoh:**
- Bottom rate: 500,000
- Percentage increase: 10%
- Starting bar: 1
- Active bar level: 3

Perhitungan:
```
price = 500,000 × (1 + 10/100)^(3 - 1)
price = 500,000 × (1.1)^2
price = 500,000 × 1.21
price = 605,000
```

## Error Responses

### 401 Unauthorized - API Key Required
```json
{
  "error": "API key required",
  "message": "Please provide a valid API key in the X-API-Key header or api_key parameter"
}
```

### 401 Unauthorized - Invalid API Key
```json
{
  "error": "Invalid API key",
  "message": "The provided API key is invalid or has been deactivated"
}
```

### 403 Forbidden - Origin Not Allowed
```json
{
  "error": "Origin not allowed",
  "message": "Your domain is not authorized to use this API key"
}
```

### 403 Forbidden - Wrong Property
```json
{
  "error": "Unauthorized",
  "message": "This API key is not authorized for this property"
}
```

### 404 Not Found - Room Type Not Found
```json
{
  "error": "Not found",
  "message": "Room type not found for this property"
}
```

## Example Usage

### JavaScript (Fetch API)

```javascript
const API_KEY = 'htk_your_api_key_here';
const PROPERTY_ID = 1;

// Get all room types and pricing
fetch(`https://your-domain.com/api/properties/${PROPERTY_ID}/room-pricing`, {
  headers: {
    'X-API-Key': API_KEY
  }
})
  .then(response => response.json())
  .then(data => {
    console.log('Property:', data.data.property);
    console.log('Room Types:', data.data.room_types);

    // Display room types
    data.data.room_types.forEach(room => {
      console.log(`${room.name}: Rp ${room.current_price.toLocaleString('id-ID')}`);
    });
  })
  .catch(error => console.error('Error:', error));
```

### jQuery

```javascript
const API_KEY = 'htk_your_api_key_here';
const PROPERTY_ID = 1;

$.ajax({
  url: `https://your-domain.com/api/properties/${PROPERTY_ID}/room-pricing`,
  headers: {
    'X-API-Key': API_KEY
  },
  success: function(data) {
    // Process data
    $('#property-name').text(data.data.property.name);

    data.data.room_types.forEach(function(room) {
      const roomHtml = `
        <div class="room-item">
          <h3>${room.name}</h3>
          <p class="price">Rp ${room.current_price.toLocaleString('id-ID')}</p>
          <p class="type">${room.type === 'hotel' ? 'Hotel Room' : 'MICE Room'}</p>
        </div>
      `;
      $('#rooms-container').append(roomHtml);
    });
  },
  error: function(xhr) {
    console.error('Error:', xhr.responseJSON);
  }
});
```

### PHP (cURL)

```php
<?php
$apiKey = 'htk_your_api_key_here';
$propertyId = 1;
$url = "https://your-domain.com/api/properties/{$propertyId}/room-pricing";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-Key: {$apiKey}"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);

    echo "Property: " . $data['data']['property']['name'] . "\n";
    echo "Occupancy: " . $data['data']['occupancy']['occupancy_percentage'] . "%\n\n";

    foreach ($data['data']['room_types'] as $room) {
        echo $room['name'] . ": Rp " . number_format($room['current_price'], 0, ',', '.') . "\n";
    }
} else {
    echo "Error: HTTP {$httpCode}\n";
    echo $response;
}
?>
```

### Python (requests)

```python
import requests

API_KEY = 'htk_your_api_key_here'
PROPERTY_ID = 1
url = f'https://your-domain.com/api/properties/{PROPERTY_ID}/room-pricing'

headers = {
    'X-API-Key': API_KEY
}

response = requests.get(url, headers=headers)

if response.status_code == 200:
    data = response.json()

    print(f"Property: {data['data']['property']['name']}")
    print(f"Occupancy: {data['data']['occupancy']['occupancy_percentage']}%\n")

    for room in data['data']['room_types']:
        print(f"{room['name']}: Rp {room['current_price']:,.0f}")
else:
    print(f"Error: {response.status_code}")
    print(response.text)
```

## Best Practices

1. **Simpan API Key dengan Aman**
   - Jangan commit API key ke version control
   - Simpan di environment variables atau file konfigurasi yang di-gitignore
   - Gunakan HTTPS untuk semua request

2. **Handle Errors**
   - Selalu cek status code response
   - Tampilkan pesan error yang user-friendly
   - Implement retry logic untuk network errors

3. **Cache Data**
   - Simpan response dalam cache untuk mengurangi API calls
   - Set cache expiry sesuai kebutuhan (misal: 5-15 menit)
   - Invalidate cache saat user melakukan refresh manual

4. **Monitoring**
   - Monitor API usage melalui `last_used_at` field
   - Track error rates
   - Set up alerts untuk unusual activity

## Manajemen API Key

### Melihat Daftar API Keys
```
GET /admin/properties/{property}/api-keys
```

### Membuat API Key Baru
```
POST /admin/properties/{property}/api-keys
```

### Edit API Key
```
PUT /admin/properties/{property}/api-keys/{apiKey}
```

### Nonaktifkan/Aktifkan API Key
```
POST /admin/properties/{property}/api-keys/{apiKey}/toggle
```

### Hapus API Key
```
DELETE /admin/properties/{property}/api-keys/{apiKey}
```

## Support

Jika ada pertanyaan atau masalah dengan API, silakan hubungi tim support HTracker.

---

**Version:** 1.0
**Last Updated:** 2025-12-10
