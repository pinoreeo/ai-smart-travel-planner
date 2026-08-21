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

### Routing Preview

```http
POST /admin/routing/preview
```

Dipakai untuk cek routing dari backend. Ini berguna buat test API key OpenRouteService tanpa perlu generate itinerary dulu.

Contoh body:

```json
{
  "from_latitude": -7.6079,
  "from_longitude": 110.2038,
  "to_latitude": -7.752,
  "to_longitude": 110.4915,
  "transport_mode": "car"
}
```

Contoh response:

```json
{
  "success": true,
  "message": "Route preview generated successfully.",
  "data": {
    "distance_km": 42.3,
    "duration_minutes": 65,
    "route_geometry": {
      "type": "LineString",
      "coordinates": []
    },
    "provider": "openrouteservice",
    "is_fallback": false
  }
}
```

Kalau `OPENROUTESERVICE_API_KEY` belum diisi atau API provider gagal, backend tetap balikin route fallback:

```json
{
  "provider": "fallback",
  "is_fallback": true
}
```

Cara ambil API key OpenRouteService:

```text
1. Buka https://openrouteservice.org
2. Sign up / login
3. Masuk ke dashboard/developer dashboard
4. Cari tab API Key
5. Copy Basic Key / API key
6. Simpan di backend .env
```

Env backend:

```env
OPENROUTESERVICE_API_KEY=isi_api_key_dari_openrouteservice
OPENROUTESERVICE_BASE_URL=https://api.openrouteservice.org
```

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

### Place Imports

```http
GET    /admin/place-imports
POST   /admin/place-imports/preview
GET    /admin/place-imports/osm/preview
POST   /admin/place-imports
GET    /admin/place-imports/{placeImport}
PATCH  /admin/place-imports/{placeImport}
DELETE /admin/place-imports/{placeImport}
POST   /admin/place-imports/{placeImport}/approve
POST   /admin/place-imports/{placeImport}/reject
```

Ini buat alur semi-otomatis data wisata.

Flow enaknya:

```text
ambil calon data dari luar
→ preview
→ simpan sebagai pending import
→ admin cek/edit
→ approve jadi destinasi draft/published
→ atau reject kalau datanya jelek/duplikat
```

Preview dari OSM/Overpass:

```http
GET /admin/place-imports/osm/preview?province=Jawa Tengah&limit=50
```

Catatan:

- endpoint ini butuh internet karena narik data dari Overpass API
- tidak butuh API key
- hasilnya belum masuk database, cuma preview
- data OSM biasanya belum punya harga tiket, jadi admin tetap perlu cek

Preview manual/bulk dari data yang sudah kamu punya:

```http
POST /admin/place-imports/preview
```

Contoh body:

```json
{
  "source": "manual",
  "items": [
    {
      "name": "Curug Contoh",
      "address": "Desa Contoh, Kabupaten Semarang",
      "city": "Kabupaten Semarang",
      "province": "Jawa Tengah",
      "latitude": -7.1234567,
      "longitude": 110.1234567,
      "category_slugs": ["alam", "petualangan"]
    }
  ]
}
```

Simpan hasil preview jadi pending import:

```http
POST /admin/place-imports
```

Body-nya sama seperti preview manual. Kalau import punya `source` dan `source_id` yang sama, data lama akan di-update selama belum approved.

Query list yang bisa dipakai:

```text
q=curug
source=osm
status=pending
province=Jawa Tengah
city=Magelang
per_page=15
page=1
```

Status import:

```text
pending    siap direview admin
duplicate  kemungkinan sudah ada di trp_places
approved   sudah dibuat jadi destinasi
rejected   ditolak admin
```

Edit hasil import sebelum approve:

```http
PATCH /admin/place-imports/{placeImport}
```

Contoh:

```json
{
  "address": "Alamat yang sudah dicek admin",
  "city": "Kabupaten Semarang",
  "ticket_price": 10000,
  "recommended_duration_minutes": 90,
  "category_slugs": ["alam", "petualangan"]
}
```

Approve jadi destinasi:

```http
POST /admin/place-imports/{placeImport}/approve
```

Default-nya jadi destinasi `draft`.

Kalau mau langsung publish:

```json
{
  "publish": true,
  "review_notes": "Data sudah dicek admin"
}
```

Reject:

```http
POST /admin/place-imports/{placeImport}/reject
```

Contoh:

```json
{
  "review_notes": "Duplikat dan datanya kurang jelas"
}
```

### Roles

```http
GET /admin/roles
GET /admin/roles/{role}
```

Dipakai dashboard admin buat ambil pilihan role ketika mau atur akses user.

Query list yang bisa dipakai:

```text
q=admin
is_active=true
per_page=15
page=1
```

### Users

```http
GET   /admin/users
GET   /admin/users/{user}
PATCH /admin/users/{user}
PUT   /admin/users/{user}/roles
```

Dipakai untuk:

- lihat user yang terdaftar
- cek role user
- assign/remove role admin kalau dibutuhkan

Query list yang bisa dipakai:

```text
q=budi
role=admin
per_page=15
page=1
```

Contoh edit user:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com"
}
```

Contoh sync role user:

```json
{
  "role_slugs": ["admin"]
}
```

Bisa juga pakai ID:

```json
{
  "role_ids": [1, 2]
}
```

Catatan: sync berarti role lama user akan diganti dengan role yang dikirim.

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

Query list yang bisa dipakai:

```text
q=jogja
user_id=3
status=saved
generation_type=ai_assisted
travel_date_from=2026-08-01
travel_date_to=2026-08-31
with_trashed=true
per_page=15
page=1
```

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

Query list yang bisa dipakai:

```text
q=pantai
user_id=3
itinerary_id=10
request_type=parse_trip_request
status=success
model_name=gpt-4.1-mini
created_from=2026-08-01
created_to=2026-08-31
per_page=15
page=1
```

## Yang Belum Masuk Backend Internal

API internal untuk client dan dashboard admin sudah ada fondasinya. Yang belum termasuk di sini adalah integrasi pihak kedua:

- upload gambar beneran ke Cloudinary/S3/local storage endpoint
- AI provider beneran untuk generate itinerary
- enrichment data wisata dari Google Places/Wikidata kalau nanti mau data lebih lengkap

## Urutan Implementasi Yang Enak

Saran lanjutannya:

1. Pakai Place Imports untuk seed awal data wisata Jawa Tengah.
2. Tentukan provider upload gambar.
3. Isi API key OpenRouteService untuk routing beneran.
4. Tentukan AI provider.
5. Baru mulai dashboard admin dan integrasi frontend/mobile dengan API yang sudah ada.

Dashboard admin sekarang sudah bisa mulai dibangun untuk input data destinasi, import destinasi semi-otomatis, kategori, fasilitas, user role, dan monitoring trip.
