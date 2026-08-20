# Backend Client API

Dokumen ini jadi pegangan buat tim Android dan web user-facing saat manggil API.

Bagian ini khusus API user biasa: login, explore destinasi, favorite, bikin itinerary, dan lihat trip. Untuk dashboard admin, lihat:

```text
docs/backend-admin-api.md
```

Pembagiannya:

```text
/api/v1/...        untuk Android dan web user-facing
/api/v1/admin/...  untuk dashboard admin
```

## Base URL

Kalau jalan lokal:

```text
http://127.0.0.1:8000/api/v1
```

Kalau port backend beda, tinggal sesuaikan host/port-nya.

## Header

Endpoint yang butuh login wajib kirim:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

Token didapat dari `POST /auth/register` atau `POST /auth/login`.

## Pola Response

Kalau sukses:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Kalau list pakai pagination:

```json
{
  "success": true,
  "message": "Places retrieved successfully.",
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 25,
      "last_page": 3,
      "from": 1,
      "to": 10
    }
  }
}
```

Kalau validasi gagal:

```json
{
  "success": false,
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Kalau belum login atau token salah:

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

Status code yang sering muncul:

```text
200 sukses
201 berhasil dibuat
401 belum login / token salah
403 tidak punya akses
404 data tidak ditemukan
422 validasi gagal
```

## Auth

### Register

```http
POST /auth/register
```

Body:

```json
{
  "name": "Victor Example",
  "email": "victor@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "device_name": "android"
}
```

Response berisi token dan data user:

```json
{
  "success": true,
  "message": "Registration successful.",
  "data": {
    "token_type": "Bearer",
    "access_token": "1|token",
    "user": {
      "id": 1,
      "name": "Victor Example",
      "email": "victor@example.com",
      "email_verified_at": null,
      "roles": [
        {
          "id": 2,
          "name": "User",
          "slug": "user"
        }
      ]
    }
  }
}
```

### Login

```http
POST /auth/login
```

Body:

```json
{
  "email": "user@travelplanner.local",
  "password": "User123!",
  "device_name": "android"
}
```

Response-nya mirip register: dapat `access_token`, `token_type`, dan data user.

### Ambil User Login

```http
GET /auth/me
```

Butuh token.

### Logout

```http
POST /auth/logout
```

Butuh token. Token yang sedang dipakai akan dihapus.

## Profile

### Lihat Profile

```http
GET /profile
```

Butuh token.

### Update Profile

```http
PATCH /profile
```

Butuh token.

Body:

```json
{
  "name": "Victor Updated",
  "email": "victor.updated@example.com"
}
```

Kirim field yang mau diubah saja.

## Master Data

### Categories

```http
GET /categories
```

Dipakai untuk chip kategori di Home, Explore, dan Planner interests.

Contoh item:

```json
{
  "id": 1,
  "name": "Alam",
  "slug": "alam",
  "description": "Wisata alam seperti air terjun, pegunungan, taman, dan pemandangan terbuka.",
  "icon": "leaf",
  "is_active": true
}
```

### Facilities

```http
GET /facilities
```

Dipakai untuk filter atau detail destinasi.

Contoh item:

```json
{
  "id": 1,
  "name": "Area Parkir",
  "slug": "area-parkir",
  "description": "Tersedia area parkir kendaraan.",
  "icon": "parking",
  "is_active": true
}
```

## Places

### List Places

```http
GET /places
```

Query yang bisa dipakai:

```text
q=curug
category=alam,keluarga
facility=toilet,spot-foto
city=Banyumas
place_type=indoor|outdoor|mixed
min_price=0
max_price=25000
featured=true
latitude=-7.42
longitude=109.23
sort=name|price_low|price_high|newest|distance
per_page=10
page=1
```

Catatan:

- Kalau kirim `latitude` dan `longitude`, response akan punya `distance_km`.
- Kalau kirim token, response bisa punya `is_favorite=true`.
- Kalau tidak kirim token, `is_favorite` default `false`.

### Featured Places

```http
GET /places/featured?limit=10
```

Dipakai untuk section Featured Destinations.

### Nearby Places

```http
GET /places/nearby?latitude=-7.42&longitude=109.23&radius_km=30
```

Wajib kirim:

```text
latitude
longitude
```

Opsional:

```text
radius_km=25
category=alam
facility=toilet
place_type=outdoor
per_page=10
```

### Search Places

```http
GET /places/search?q=curug
```

Filter yang didukung sama seperti `GET /places`.

### Detail Place

```http
GET /places/{slug}
```

Contoh:

```http
GET /places/curug-bayan
```

Data detail berisi info lengkap untuk halaman Destination Detail:

```json
{
  "id": 2,
  "name": "Curug Bayan",
  "slug": "curug-bayan",
  "short_description": "Air terjun alami yang cocok untuk wisata santai dan fotografi.",
  "city": "Banyumas",
  "province": "Jawa Tengah",
  "place_type": "outdoor",
  "ticket_price": 15000,
  "recommended_duration_minutes": 120,
  "is_featured": true,
  "is_favorite": false,
  "categories": [],
  "primary_image": {},
  "description": "Curug Bayan merupakan wisata air terjun...",
  "address": "Ketenger, Kecamatan Baturraden, Kabupaten Banyumas",
  "postal_code": null,
  "latitude": -7.32685,
  "longitude": 109.22135,
  "parking_price_motorcycle": 3000,
  "parking_price_car": 5000,
  "phone": null,
  "website_url": null,
  "instagram_url": null,
  "last_verified_at": "2026-08-13T12:34:36.000000Z",
  "facilities": [],
  "opening_hours": [],
  "images": []
}
```

## Favorites

Semua endpoint favorites butuh token.

### List Favorites

```http
GET /favorites
```

Query opsional:

```text
per_page=10
page=1
```

### Tambah Favorite

```http
POST /favorites/{place}
```

`{place}` pakai ID place, bukan slug.

Contoh:

```http
POST /favorites/2
```

### Hapus Favorite

```http
DELETE /favorites/{place}
```

Response akan mengembalikan `is_favorite=false`.

## Planner

Semua endpoint planner butuh token.

### Buat Draft

```http
POST /planner/drafts
```

Body boleh partial. Cocok untuk menyimpan progress step planner.

Contoh:

```json
{
  "title": "Draft Banyumas Day Trip",
  "travel_date": "2026-08-20",
  "start_time": "08:00",
  "end_time": "17:00",
  "start_address": "Purwokerto",
  "start_latitude": -7.42465,
  "start_longitude": 109.23179,
  "number_of_people": 2,
  "transport_mode": "motorcycle",
  "travel_style": "balanced",
  "budget": 500000,
  "category_slugs": ["alam", "keluarga"],
  "notes": "Prefer outdoor places"
}
```

### Update Draft

```http
PATCH /planner/drafts/{itinerary}
```

Body boleh isi field apa pun dari draft.

### Generate Itinerary

```http
POST /planner/generate
```

Body minimal:

```json
{
  "travel_date": "2026-08-20",
  "start_time": "08:00",
  "end_time": "17:00",
  "start_address": "Purwokerto",
  "start_latitude": -7.42465,
  "start_longitude": 109.23179,
  "number_of_people": 2,
  "transport_mode": "motorcycle",
  "travel_style": "balanced",
  "budget": 500000,
  "category_slugs": ["alam", "keluarga"]
}
```

Bisa juga kirim:

```json
{
  "itinerary_id": 1,
  "title": "Explore Banyumas",
  "category_ids": [1, 5]
}
```

Untuk sekarang, generate itinerary belum memanggil AI provider. Backend masih memilih destinasi secara deterministic dari data tempat yang sudah ada, lalu menghitung estimasi waktu, jarak, dan budget.

Yang sudah dihitung:

- waktu perjalanan
- waktu kunjungan
- ticket cost
- parking cost
- transport cost
- food estimate
- total dan remaining budget
- route geometry sederhana

Yang belum:

- AI recommendation beneran
- route jalan asli dari maps provider
- geocoding alamat

### Modify Itinerary

```http
POST /planner/{itinerary}/modify
```

Contoh:

```json
{
  "budget": 750000,
  "travel_style": "packed",
  "regenerate": true
}
```

Kalau `regenerate=true`, item itinerary akan dibangun ulang.

## Trips

Semua endpoint trips butuh token.

### List Trips

```http
GET /trips
```

Query opsional:

```text
status=draft|generated|saved|completed|cancelled|upcoming|past
per_page=10
page=1
```

### Buat Trip Manual

```http
POST /trips
```

Contoh:

```json
{
  "title": "Manual Trip",
  "travel_date": "2026-08-20",
  "start_time": "08:00",
  "end_time": "17:00",
  "start_address": "Purwokerto",
  "start_latitude": -7.42465,
  "start_longitude": 109.23179,
  "number_of_people": 2,
  "transport_mode": "motorcycle",
  "travel_style": "balanced",
  "budget": 500000,
  "category_slugs": ["alam"],
  "status": "draft"
}
```

### Detail Trip

```http
GET /trips/{itinerary}
```

### Update Trip

```http
PATCH /trips/{itinerary}
```

### Delete Trip

```http
DELETE /trips/{itinerary}
```

### Save Trip

```http
POST /trips/{itinerary}/save
```

Ini mengubah status itinerary menjadi `saved`.

### Timeline

```http
GET /trips/{itinerary}/timeline
```

Dipakai untuk tab Timeline di generated itinerary.

### Map

```http
GET /trips/{itinerary}/map
```

Contoh bentuk data:

```json
{
  "start": {
    "address": "Purwokerto",
    "latitude": -7.42465,
    "longitude": 109.23179
  },
  "points": [
    {
      "sequence": 1,
      "place_id": 2,
      "name": "Curug Bayan",
      "latitude": -7.32685,
      "longitude": 109.22135,
      "arrival_time": "08:20",
      "departure_time": "10:20",
      "route_geometry": {
        "type": "LineString",
        "coordinates": [
          [109.23179, -7.42465],
          [109.22135, -7.32685]
        ]
      }
    }
  ]
}
```

### Budget

```http
GET /trips/{itinerary}/budget
```

Contoh:

```json
{
  "budget": 500000,
  "tickets": 100000,
  "parking": 9000,
  "transportation": 60000,
  "food_estimate": 120000,
  "total": 289000,
  "remaining": 211000
}
```

## Flow Yang Disarankan

Untuk Android atau web user-facing, flow paling aman:

1. Login lewat `POST /auth/login`.
2. Simpan `data.access_token`.
3. Ambil kategori dengan `GET /categories`.
4. Ambil featured destinations dengan `GET /places/featured`.
5. Search pakai `GET /places/search?q=curug`.
6. Buka detail destinasi pakai `GET /places/{slug}`.
7. Toggle favorite pakai `POST /favorites/{place}` atau `DELETE /favorites/{place}`.
8. Generate trip pakai `POST /planner/generate`.
9. Save hasilnya pakai `POST /trips/{itinerary}/save`.
10. Tampilkan trip tersimpan pakai `GET /trips?status=saved`.
11. Ambil timeline/map/budget dari endpoint trips.

## Akun Demo

```text
Admin:
email: admin@travelplanner.local
password: Admin123!

User:
email: user@travelplanner.local
password: User123!
```

Untuk integrasi Android/web user-facing, pakai akun `User`.

## Catatan Saat Ini

- Planner belum pakai AI provider.
- Routing belum pakai maps provider, masih estimasi jarak lurus dan GeoJSON sederhana.
- Geocoding alamat belum ada.
- Tabel `trp_ai_requests` sudah ada, tapi belum dipakai oleh planner.
- Upload image dan dashboard admin tidak masuk scope Client API.

