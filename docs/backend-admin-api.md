# Backend Admin API

Dokumen ini buat pegangan dashboard admin web.

Bedanya dengan Client API:

```text
/api/v1/...        untuk Android dan web user-facing
/api/v1/admin/...  untuk dashboard admin
```

Dokumen API untuk Android dan web user-facing ada di:

```text
docs/backend-client-api.md
```

## Akses Admin

Admin API tetap pakai token dari endpoint login yang sama:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

Bedanya, user harus punya role:

```text
admin
```

Kalau belum login:

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

Kalau login sebagai user biasa:

```json
{
  "success": false,
  "message": "Forbidden.",
  "data": null
}
```

## Yang Sudah Ada Sekarang

### Health Check Admin

```http
GET /admin/health
```

Butuh token admin.

Response:

```json
{
  "success": true,
  "message": "Admin API is available.",
  "data": {
    "scope": "admin"
  }
}
```

Endpoint ini dipakai untuk memastikan token admin dan middleware admin sudah jalan.

### Categories

```http
GET    /admin/categories
POST   /admin/categories
GET    /admin/categories/{category}
PATCH  /admin/categories/{category}
DELETE /admin/categories/{category}
POST   /admin/categories/{category}/restore
```

Dipakai admin untuk:

- tambah kategori
- edit nama, slug, icon, dan deskripsi
- aktif/nonaktif kategori
- hapus kategori kalau memang aman
- restore kategori yang kena soft delete

Query list yang bisa dipakai:

```text
q=alam
is_active=true
with_trashed=true
per_page=15
page=1
```

### Facilities

```http
GET    /admin/facilities
POST   /admin/facilities
GET    /admin/facilities/{facility}
PATCH  /admin/facilities/{facility}
DELETE /admin/facilities/{facility}
POST   /admin/facilities/{facility}/restore
```

Dipakai admin untuk:

- tambah fasilitas
- edit nama, slug, icon, dan deskripsi
- aktif/nonaktif fasilitas
- restore fasilitas yang kena soft delete

Query list yang bisa dipakai:

```text
q=parkir
is_active=true
with_trashed=true
per_page=15
page=1
```

### Places

```http
GET    /admin/places
POST   /admin/places
GET    /admin/places/{place}
PATCH  /admin/places/{place}
DELETE /admin/places/{place}
POST   /admin/places/{place}/publish
POST   /admin/places/{place}/unpublish
POST   /admin/places/{place}/restore
```

Dipakai admin untuk kelola destinasi:

- tambah destinasi baru
- edit deskripsi, alamat, koordinat, harga, durasi, dan tipe tempat
- set status `draft`, `published`, atau `inactive`
- tandai destinasi sebagai featured
- update `last_verified_at`

Query list yang bisa dipakai:

```text
q=borobudur
status=published
city=Magelang
province=Jawa Tengah
place_type=outdoor
featured=true
category=alam,budaya
facility=parkir,toilet
with_trashed=true
sort=newest
per_page=15
page=1
```

Nilai `sort` yang tersedia:

```text
name
newest
updated
price_low
price_high
```

Contoh body untuk tambah destinasi:

```json
{
  "name": "Tebing Breksi",
  "slug": "tebing-breksi",
  "short_description": "Destinasi tebing batu dengan view sunset.",
  "description": "Cocok buat foto, lihat pemandangan, dan jalan santai.",
  "address": "Sambirejo, Prambanan",
  "city": "Sleman",
  "province": "DI Yogyakarta",
  "postal_code": "55572",
  "latitude": -7.7816,
  "longitude": 110.5046,
  "ticket_price": 10000,
  "parking_price_motorcycle": 3000,
  "parking_price_car": 5000,
  "recommended_duration_minutes": 90,
  "place_type": "outdoor",
  "phone": "0274123456",
  "website_url": "https://example.com/tebing-breksi",
  "instagram_url": "https://instagram.com/tebingbreksi",
  "status": "draft",
  "is_featured": false,
  "category_ids": [1, 2],
  "facilities": [
    {
      "facility_id": 1,
      "notes": "Area parkir motor dan mobil tersedia"
    }
  ],
  "opening_hours": [
    {
      "day_of_week": "monday",
      "sort_order": 1,
      "open_time": "08:00",
      "close_time": "17:00",
      "is_closed": false
    }
  ],
  "images": [
    {
      "image_url": "https://example.com/images/tebing-breksi.jpg",
      "caption": "Area utama Tebing Breksi",
      "alt_text": "Tebing Breksi saat sore hari",
      "is_primary": true,
      "sort_order": 1
    }
  ]
}
```

Untuk edit destinasi, kirim field yang mau diubah saja:

```http
PATCH /admin/places/{place}
```

Contoh:

```json
{
  "ticket_price": 15000,
  "is_featured": true,
  "status": "published"
}
```

### Place Categories

```http
PUT /admin/places/{place}/categories
```

Dipakai untuk sync kategori sebuah destinasi. Artinya daftar kategori lama akan diganti dengan daftar yang dikirim.

Contoh body:

```json
{
  "category_ids": [1, 5, 7]
}
```

### Place Facilities

```http
PUT /admin/places/{place}/facilities
```

Dipakai untuk sync fasilitas sebuah destinasi. Bisa kasih catatan per fasilitas, misalnya:

```json
{
  "facilities": [
    {
      "facility_id": 1,
      "notes": "Parkir motor dan mobil tersedia"
    }
  ]
}
```

### Opening Hours

```http
GET    /admin/places/{place}/opening-hours
POST   /admin/places/{place}/opening-hours
PATCH  /admin/places/{place}/opening-hours/{openingHour}
DELETE /admin/places/{place}/opening-hours/{openingHour}
```

Dipakai untuk kelola jam buka.

Schema sudah mendukung lebih dari satu slot per hari lewat `sort_order`, jadi bisa handle kasus seperti buka pagi lalu buka lagi sore.

Contoh tambah jam buka:

```json
{
  "day_of_week": "saturday",
  "sort_order": 1,
  "open_time": "08:00",
  "close_time": "18:00",
  "is_closed": false,
  "notes": "Weekend biasanya lebih ramai"
}
```

Kalau tutup:

```json
{
  "day_of_week": "monday",
  "sort_order": 1,
  "is_closed": true,
  "notes": "Tutup untuk maintenance"
}
```

### Place Images

```http
GET    /admin/places/{place}/images
POST   /admin/places/{place}/images
PATCH  /admin/places/{place}/images/{image}
DELETE /admin/places/{place}/images/{image}
POST   /admin/places/{place}/images/{image}/primary
```

Dipakai untuk:

- tambah gambar destinasi
- edit caption dan alt text
- ubah urutan gambar
- set primary image
- hapus gambar

Catatan: provider upload gambar belum ditentukan. Bisa pakai local storage dulu, lalu nanti pindah ke Cloudinary/S3 kalau perlu.

Contoh tambah gambar:

```json
{
  "image_url": "https://example.com/images/tempat.jpg",
  "public_id": "places/tempat",
  "caption": "Spot foto utama",
  "alt_text": "Area destinasi dengan pemandangan terbuka",
  "is_primary": true,
  "sort_order": 1
}
```

Kalau `is_primary=true`, gambar primary yang lama otomatis diganti.

## Endpoint Admin Yang Perlu Dibuat

Bagian di bawah ini belum diimplementasikan. Ini daftar kerja berikutnya supaya dashboard admin makin lengkap.

### Users

```http
GET   /admin/users
GET   /admin/users/{user}
PATCH /admin/users/{user}
```

Dipakai untuk:

- lihat user yang terdaftar
- cek role user
- assign/remove role admin kalau dibutuhkan

### Itineraries

```http
GET /admin/itineraries
GET /admin/itineraries/{itinerary}
```

Dipakai admin untuk monitor itinerary yang dibuat user.

Ini berguna untuk:

- cek hasil planner
- debugging rekomendasi
- lihat pola penggunaan

### AI Requests

```http
GET /admin/ai-requests
GET /admin/ai-requests/{aiRequest}
```

Dipakai nanti setelah AI provider diintegrasikan.

Isinya bisa buat cek:

- prompt/request user
- parameter hasil parsing
- model yang dipakai
- status request
- token usage
- error message kalau gagal

## Urutan Implementasi Yang Enak

Saran urutannya:

1. Users dan role management.
2. Itinerary monitoring.
3. AI request monitoring.
4. Integrasi upload gambar beneran, misalnya Cloudinary atau S3.

Categories, Facilities, Places, dan relasi place sudah bisa dipakai untuk mulai input data dari dashboard admin.

