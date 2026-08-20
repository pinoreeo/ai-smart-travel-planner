# Backend Admin API Documentation

Dokumentasi ini adalah rencana kontrak Admin API untuk dashboard web admin AI Smart Travel Planner.

## Scope

Admin API berbeda dari Client API.

```text
/api/v1/...        Client API untuk Android dan web user-facing
/api/v1/admin/...  Admin API untuk dashboard admin web
```

Client API saat ini sudah didokumentasikan di:

```text
docs/backend-client-api.md
```

## Authentication

Admin API memakai Bearer token dari endpoint client auth yang sama:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

User juga wajib punya role:

```text
admin
```

Jika token tidak valid:

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

Jika user bukan admin:

```json
{
  "success": false,
  "message": "Forbidden.",
  "data": null
}
```

## Current Admin Route

### Health

```http
GET /admin/health
```

Protected: yes, admin only.

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

## Planned Admin Endpoints

### Categories

```http
GET    /admin/categories
POST   /admin/categories
GET    /admin/categories/{category}
PATCH  /admin/categories/{category}
DELETE /admin/categories/{category}
```

Purpose:

- Manage category master data.
- Enable/disable category.
- Edit icon and description.

### Facilities

```http
GET    /admin/facilities
POST   /admin/facilities
GET    /admin/facilities/{facility}
PATCH  /admin/facilities/{facility}
DELETE /admin/facilities/{facility}
```

Purpose:

- Manage facility master data.
- Enable/disable facility.
- Edit icon and description.

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

Purpose:

- Manage destination data.
- Draft/publish/inactive workflow.
- Manage price, location, duration, type, and verification status.

### Place Categories

```http
PUT /admin/places/{place}/categories
```

Purpose:

- Sync categories for a place.

### Place Facilities

```http
PUT /admin/places/{place}/facilities
```

Purpose:

- Sync facilities for a place.
- Store facility notes where needed.

### Place Opening Hours

```http
GET    /admin/places/{place}/opening-hours
POST   /admin/places/{place}/opening-hours
PATCH  /admin/places/{place}/opening-hours/{openingHour}
DELETE /admin/places/{place}/opening-hours/{openingHour}
```

Purpose:

- Manage weekly opening hours.
- Support multiple time slots per day through `sort_order`.

### Place Images

```http
GET    /admin/places/{place}/images
POST   /admin/places/{place}/images
PATCH  /admin/places/{place}/images/{image}
DELETE /admin/places/{place}/images/{image}
POST   /admin/places/{place}/images/{image}/primary
```

Purpose:

- Manage destination gallery.
- Set primary image.
- Reorder images.

Image upload storage provider is not decided yet.

### Users

```http
GET   /admin/users
GET   /admin/users/{user}
PATCH /admin/users/{user}
```

Purpose:

- View registered users.
- Assign or remove roles.
- Support admin operations.

### Itineraries

```http
GET /admin/itineraries
GET /admin/itineraries/{itinerary}
```

Purpose:

- Monitor generated trips.
- Debug planner results.
- Review usage patterns.

### AI Requests

```http
GET /admin/ai-requests
GET /admin/ai-requests/{aiRequest}
```

Purpose:

- Inspect AI generation logs.
- Track model name, token usage, status, and errors.

## Implementation Priority

Recommended order:

1. Admin middleware and health route.
2. Category and facility CRUD.
3. Place CRUD.
4. Place relations: categories, facilities, opening hours, images.
5. User and role management.
6. Itinerary and AI request monitoring.

