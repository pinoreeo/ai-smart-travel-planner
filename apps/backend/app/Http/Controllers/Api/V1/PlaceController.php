<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PlaceCardResource;
use App\Http\Resources\Api\V1\PlaceDetailResource;
use App\Models\Place;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $this->validatedListFilters($request);
        $userId = $request->user('sanctum')?->id;

        $query = $this->basePlaceQuery($userId);
        $this->applyFilters($query, $filters);

        $hasDistance = isset($filters['latitude'], $filters['longitude']);

        if ($hasDistance) {
            $this->selectDistance($query, (float) $filters['latitude'], (float) $filters['longitude']);
        }

        $this->applySort($query, $filters, $hasDistance);

        $places = $query->paginate($this->perPage($request));

        return $this->paginated(
            $places,
            PlaceCardResource::class,
            'Places retrieved successfully.'
        );
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 10), 1), 20);

        $places = $this->basePlaceQuery($request->user('sanctum')?->id)
            ->where('is_featured', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $this->success(
            PlaceCardResource::collection($places)->resolve($request),
            'Featured places retrieved successfully.'
        );
    }

    public function nearby(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['sometimes', 'numeric', 'min:1', 'max:100'],
            'category' => ['sometimes', 'string'],
            'facility' => ['sometimes', 'string'],
            'place_type' => ['sometimes', 'string', 'in:indoor,outdoor,mixed'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $latitude = (float) $filters['latitude'];
        $longitude = (float) $filters['longitude'];
        $radiusKm = (float) ($filters['radius_km'] ?? 25);

        $query = $this->basePlaceQuery($request->user('sanctum')?->id);
        $this->applyFilters($query, $filters);
        $this->selectDistance($query, $latitude, $longitude);
        $this->whereWithinRadius($query, $latitude, $longitude, $radiusKm);

        $places = $query
            ->orderBy('distance_km')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $places,
            PlaceCardResource::class,
            'Nearby places retrieved successfully.'
        );
    }

    public function search(Request $request): JsonResponse
    {
        $filters = $this->validatedListFilters($request);

        $query = $this->basePlaceQuery($request->user('sanctum')?->id);
        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        $places = $query->paginate($this->perPage($request));

        return $this->paginated(
            $places,
            PlaceCardResource::class,
            'Search results retrieved successfully.'
        );
    }

    public function show(Request $request, Place $place): JsonResponse
    {
        abort_if($place->status !== 'published', 404);

        $place->setAttribute(
            'is_favorite',
            $request->user('sanctum')
                ? $place->favorites()->where('user_id', $request->user('sanctum')->id)->exists()
                : false
        );

        $place->load([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'facilities' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'openingHours' => fn ($query) => $this->orderOpeningHours($query),
            'images',
            'primaryImage',
        ]);

        return $this->success(
            (new PlaceDetailResource($place))->resolve($request),
            'Place detail retrieved successfully.'
        );
    }

    private function basePlaceQuery(?int $userId = null): Builder
    {
        $query = Place::query()
            ->select('trp_places.*')
            ->where('status', 'published')
            ->with([
                'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'primaryImage',
            ]);

        if ($userId !== null) {
            $query->withExists([
                'favorites as is_favorite' => fn ($query) => $query->where('user_id', $userId),
            ]);
        }

        return $query;
    }

    private function orderOpeningHours($query)
    {
        return $query
            ->orderByRaw(
                "case day_of_week
                    when 'monday' then 1
                    when 'tuesday' then 2
                    when 'wednesday' then 3
                    when 'thursday' then 4
                    when 'friday' then 5
                    when 'saturday' then 6
                    when 'sunday' then 7
                    else 8
                end"
            )
            ->orderBy('sort_order');
    }

    private function validatedListFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'category' => ['sometimes', 'string'],
            'facility' => ['sometimes', 'string'],
            'city' => ['sometimes', 'string', 'max:100'],
            'place_type' => ['sometimes', 'string', 'in:indoor,outdoor,mixed'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'featured' => ['sometimes', 'boolean'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'sort' => ['sometimes', 'string', 'in:name,price_low,price_high,newest,distance'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('short_description', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $categorySlugs = $this->splitSlugs($filters['category']);

            $query->whereHas('categories', function (Builder $query) use ($categorySlugs): void {
                $query->whereIn('slug', $categorySlugs);
            });
        }

        if (! empty($filters['facility'])) {
            $facilitySlugs = $this->splitSlugs($filters['facility']);

            $query->whereHas('facilities', function (Builder $query) use ($facilitySlugs): void {
                $query->whereIn('slug', $facilitySlugs);
            });
        }

        if (! empty($filters['city'])) {
            $query->where('city', 'ilike', $filters['city']);
        }

        if (! empty($filters['place_type'])) {
            $query->where('place_type', $filters['place_type']);
        }

        if (isset($filters['min_price'])) {
            $query->where('ticket_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('ticket_price', '<=', $filters['max_price']);
        }

        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', (bool) $filters['featured']);
        }
    }

    private function applySort(Builder $query, array $filters, bool $hasDistance = false): void
    {
        match ($filters['sort'] ?? null) {
            'name' => $query->orderBy('name'),
            'price_low' => $query->orderBy('ticket_price')->orderBy('name'),
            'price_high' => $query->orderByDesc('ticket_price')->orderBy('name'),
            'newest' => $query->latest('id'),
            'distance' => $hasDistance
                ? $query->orderBy('distance_km')
                : $query->orderByDesc('is_featured')->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('name'),
        };
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 10), 1), 50);
    }

    private function splitSlugs(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $slug): string => trim($slug),
            explode(',', $value)
        )));
    }

    private function selectDistance(Builder $query, float $latitude, float $longitude): void
    {
        $query->selectRaw(
            $this->distanceSql().' as distance_km',
            [$latitude, $longitude, $latitude]
        );
    }

    private function whereWithinRadius(Builder $query, float $latitude, float $longitude, float $radiusKm): void
    {
        $query->whereRaw(
            $this->distanceSql().' <= ?',
            [$latitude, $longitude, $latitude, $radiusKm]
        );
    }

    private function distanceSql(): string
    {
        return '(6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))';
    }
}
