# Backend Client API Documentation

Dokumentasi ini adalah kontrak REST API client untuk AI Smart Travel Planner. Target pembaca adalah tim Android dan tim web user-facing.

API ini bukan untuk dashboard admin. Kontrak Admin API dipisahkan di:

```text
docs/backend-admin-api.md
```

Pembagian scope:

```text
/api/v1/...        Client API untuk Android dan web user-facing
/api/v1/admin/...  Admin API untuk dashboard admin web
```

## Base URL

Local development:

```text
http://127.0.0.1:8000/api/v1
```

Jika backend dijalankan di port lain, ganti host/port sesuai environment lokal.

## Authentication

API memakai Laravel Sanctum personal access token.

Protected endpoint wajib memakai header:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

Token didapat dari endpoint `register` atau `login`.

## Response Format

Response sukses:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Response sukses dengan pagination:

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

Validation error:

```json
{
  "success": false,
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Unauthenticated:

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

Common status codes:

```text
200 OK
201 Created
401 Unauthenticated
404 Not Found
422 Validation Error
```

## Auth API

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

Response `201`:

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

Response `200`:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token_type": "Bearer",
    "access_token": "1|token",
    "user": {
      "id": 1,
      "name": "Demo User",
      "email": "user@travelplanner.local",
      "email_verified_at": "2026-08-20T00:00:00.000000Z",
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

### Me

```http
GET /auth/me
```

Protected: yes.

### Logout

```http
POST /auth/logout
```

Protected: yes.

Response:

```json
{
  "success": true,
  "message": "Logout successful.",
  "data": null
}
```

## Profile API

### Get Profile

```http
GET /profile
```

Protected: yes.

### Update Profile

```http
PATCH /profile
```

Protected: yes.

Body:

```json
{
  "name": "Victor Updated",
  "email": "victor.updated@example.com"
}
```

Fields are optional, but at least one field should be sent by the client.

## Master Data API

### Categories

```http
GET /categories
```

Response:

```json
{
  "success": true,
  "message": "Categories retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Alam",
      "slug": "alam",
      "description": "Wisata alam seperti air terjun, pegunungan, taman, dan pemandangan terbuka.",
      "icon": "leaf",
      "is_active": true
    }
  ]
}
```

### Facilities

```http
GET /facilities
```

Response item shape:

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

## Places API

### List Places

```http
GET /places
```

Optional query:

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

Notes:

- `distance_km` appears when `latitude` and `longitude` are sent.
- `is_favorite` is `true` only when request includes a valid Bearer token and the user has favorited the place.

### Featured Places

```http
GET /places/featured?limit=10
```

### Nearby Places

```http
GET /places/nearby?latitude=-7.42&longitude=109.23&radius_km=30
```

Required query:

```text
latitude
longitude
```

Optional query:

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

Supports the same filters as `GET /places`.

### Place Detail

```http
GET /places/{slug}
```

Example:

```http
GET /places/curug-bayan
```

Response data includes:

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

## Favorites API

All favorites endpoints are protected.

### List Favorites

```http
GET /favorites
```

Optional query:

```text
per_page=10
page=1
```

### Add Favorite

```http
POST /favorites/{place}
```

`{place}` uses place ID, not slug.

Example:

```http
POST /favorites/2
```

Response:

```json
{
  "success": true,
  "message": "Place added to favorites.",
  "data": {
    "favorite_id": 1,
    "is_favorite": true,
    "place": {}
  }
}
```

### Remove Favorite

```http
DELETE /favorites/{place}
```

Response:

```json
{
  "success": true,
  "message": "Place removed from favorites.",
  "data": {
    "is_favorite": false,
    "place": {}
  }
}
```

## Planner API

All planner endpoints are protected.

### Create Draft

```http
POST /planner/drafts
```

Body can be partial:

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

Body can contain any field from Create Draft.

### Generate Itinerary

```http
POST /planner/generate
```

Required body:

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

Optional:

```json
{
  "itinerary_id": 1,
  "title": "Explore Banyumas",
  "category_ids": [1, 5]
}
```

Current behavior:

- Generates itinerary deterministically from published places.
- Filters by selected categories.
- Estimates travel duration using transport mode.
- Estimates ticket, parking, transport, and food costs.
- Creates timeline items and simple GeoJSON route lines.

Important limitation:

- This endpoint does not call an AI provider yet.
- Route geometry is currently a straight `LineString`, not real road routing.

### Modify Itinerary

```http
POST /planner/{itinerary}/modify
```

Body:

```json
{
  "budget": 750000,
  "travel_style": "packed",
  "regenerate": true
}
```

Set `regenerate=true` to rebuild itinerary items.

## Trips API

All trips endpoints are protected.

### List Trips

```http
GET /trips
```

Optional query:

```text
status=draft|generated|saved|completed|cancelled|upcoming|past
per_page=10
page=1
```

### Create Trip

```http
POST /trips
```

Body:

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

### Trip Detail

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

Changes itinerary status to `saved`.

### Trip Timeline

```http
GET /trips/{itinerary}/timeline
```

### Trip Map

```http
GET /trips/{itinerary}/map
```

Response data:

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

### Trip Budget

```http
GET /trips/{itinerary}/budget
```

Response data:

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

## Main Android Flow

Recommended integration order:

1. `POST /auth/login`
2. Save `data.access_token` securely.
3. `GET /categories`
4. `GET /places/featured`
5. `GET /places/search?q=curug`
6. `GET /places/{slug}`
7. `POST /favorites/{place}` or `DELETE /favorites/{place}`
8. `POST /planner/generate`
9. `POST /trips/{itinerary}/save`
10. `GET /trips?status=saved`
11. `GET /trips/{itinerary}/timeline`
12. `GET /trips/{itinerary}/map`
13. `GET /trips/{itinerary}/budget`

## Seeded Demo Accounts

```text
Admin:
email: admin@travelplanner.local
password: Admin123!

User:
email: user@travelplanner.local
password: User123!
```

Use the user account for Android integration testing.

## Current Limitations

- Planner generation is deterministic and does not call an AI provider yet.
- Routing is estimated with haversine distance and simple GeoJSON lines.
- Geocoding/reverse geocoding is not integrated yet.
- `trp_ai_requests` exists but is not yet written by planner endpoints.
- Image upload/admin panel is not included in this internal mobile API scope.
