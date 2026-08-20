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

## Endpoint Admin Yang Perlu Dibuat

Bagian di bawah ini belum diimplementasikan. Ini daftar kerja supaya dashboard admin tidak kecampur dengan API Android/user.

### Places

```http
GET    /admin/places
POST   /admin/places
GET    /admin/places/{place}
PATCH  /admin/places/{place}
DELETE /admin/places/{place}
POST   /admin/places/{place}/publish
POST   /admin/places/{place}/unpublish
```

Dipakai admin untuk kelola destinasi:

- tambah destinasi baru
- edit deskripsi, alamat, koordinat, harga, durasi, dan tipe tempat
- set status `draft`, `published`, atau `inactive`
- tandai destinasi sebagai featured
- update `last_verified_at`

### Place Categories

```http
PUT /admin/places/{place}/categories
```

Dipakai untuk sync kategori sebuah destinasi.

Contoh body nantinya:

```json
{
  "category_ids": [1, 5, 7]
}
```

### Place Facilities

```http
PUT /admin/places/{place}/facilities
```

Dipakai untuk sync fasilitas sebuah destinasi.

Nanti bisa support catatan per fasilitas, misalnya:

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

1. Categories CRUD.
2. Facilities CRUD.
3. Places CRUD.
4. Relasi place: categories, facilities, opening hours, images.
5. Users dan role management.
6. Itinerary monitoring.
7. AI request monitoring.

Kalau mau cepat punya dashboard admin yang bisa dipakai input data, mulai dari Categories, Facilities, dan Places dulu.

