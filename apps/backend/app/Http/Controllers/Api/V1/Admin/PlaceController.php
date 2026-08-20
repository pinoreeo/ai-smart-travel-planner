<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\PlaceResource;
use App\Models\Place;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlaceController extends Controller
{
    use RespondsWithApiResponse;

    private const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'inactive'])],
            'city' => ['sometimes', 'string', 'max:100'],
            'province' => ['sometimes', 'string', 'max:100'],
            'place_type' => ['sometimes', 'string', Rule::in(['indoor', 'outdoor', 'mixed'])],
            'featured' => ['sometimes', 'boolean'],
            'category' => ['sometimes', 'string', 'max:255'],
            'facility' => ['sometimes', 'string', 'max:255'],
            'with_trashed' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', Rule::in(['name', 'newest', 'updated', 'price_low', 'price_high'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Place::query()
            ->with(['categories', 'facilities', 'primaryImage'])
            ->withCount(['categories', 'facilities', 'openingHours', 'images', 'favorites', 'itineraryItems']);

        if (($filters['with_trashed'] ?? false) === true) {
            $query->withTrashed();
        }

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        $places = $query->paginate($this->perPage($request));

        return $this->paginated(
            $places,
            PlaceResource::class,
            'Admin places retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $userId = $request->user()->id;

        $place = DB::transaction(function () use ($data, $userId): Place {
            $place = Place::create([
                ...$this->placeAttributes($data),
                'status' => $data['status'] ?? 'draft',
                'is_featured' => $data['is_featured'] ?? false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if (array_key_exists('category_ids', $data)) {
                $place->categories()->sync($data['category_ids']);
            }

            if (array_key_exists('facilities', $data)) {
                $place->facilities()->sync($this->facilitySyncData($data['facilities']));
            }

            if (array_key_exists('opening_hours', $data)) {
                $this->replaceOpeningHours($place, $data['opening_hours']);
            }

            if (array_key_exists('images', $data)) {
                $this->replaceImages($place, $data['images'], $userId);
            }

            return $place;
        });

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place created successfully.',
            201
        );
    }

    public function show(Request $request, Place $place): JsonResponse
    {
        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Admin place detail retrieved successfully.'
        );
    }

    public function update(Request $request, Place $place): JsonResponse
    {
        $data = $this->validatedData($request, $place);
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $place, $userId): void {
            $place->update([
                ...$this->placeAttributes($data),
                'updated_by' => $userId,
            ]);

            if (array_key_exists('category_ids', $data)) {
                $place->categories()->sync($data['category_ids']);
            }

            if (array_key_exists('facilities', $data)) {
                $place->facilities()->sync($this->facilitySyncData($data['facilities']));
            }

            if (array_key_exists('opening_hours', $data)) {
                $this->replaceOpeningHours($place, $data['opening_hours']);
            }

            if (array_key_exists('images', $data)) {
                $this->replaceImages($place, $data['images'], $userId);
            }
        });

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place updated successfully.'
        );
    }

    public function destroy(Place $place): JsonResponse
    {
        $place->delete();

        return $this->success(null, 'Place deleted successfully.');
    }

    public function restore(Request $request, int $place): JsonResponse
    {
        $place = Place::withTrashed()->findOrFail($place);
        $place->restore();

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place restored successfully.'
        );
    }

    public function publish(Request $request, Place $place): JsonResponse
    {
        $place->update([
            'status' => 'published',
            'last_verified_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place published successfully.'
        );
    }

    public function unpublish(Request $request, Place $place): JsonResponse
    {
        $place->update([
            'status' => 'draft',
            'updated_by' => $request->user()->id,
        ]);

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place unpublished successfully.'
        );
    }

    public function syncCategories(Request $request, Place $place): JsonResponse
    {
        $data = $request->validate([
            'category_ids' => ['required', 'array'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('trp_categories', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $place->categories()->sync($data['category_ids']);
        $place->update(['updated_by' => $request->user()->id]);

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place categories synced successfully.'
        );
    }

    public function syncFacilities(Request $request, Place $place): JsonResponse
    {
        $data = $request->validate([
            'facilities' => ['required', 'array'],
            'facilities.*.facility_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('trp_facilities', 'id')->whereNull('deleted_at'),
            ],
            'facilities.*.notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $place->facilities()->sync($this->facilitySyncData($data['facilities']));
        $place->update(['updated_by' => $request->user()->id]);

        return $this->success(
            (new PlaceResource($this->loadPlace($place)))->resolve($request),
            'Place facilities synced successfully.'
        );
    }

    private function validatedData(Request $request, ?Place $place = null): array
    {
        $required = $place ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:150'],
            'slug' => [
                $required,
                'string',
                'max:180',
                Rule::unique('trp_places', 'slug')->ignore($place?->id),
            ],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'address' => [$required, 'string'],
            'city' => [$required, 'string', 'max:100'],
            'province' => [$required, 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:15'],
            'latitude' => [$required, 'numeric', 'between:-90,90'],
            'longitude' => [$required, 'numeric', 'between:-180,180'],
            'ticket_price' => ['sometimes', 'numeric', 'min:0'],
            'parking_price_motorcycle' => ['sometimes', 'numeric', 'min:0'],
            'parking_price_car' => ['sometimes', 'numeric', 'min:0'],
            'recommended_duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:1440'],
            'place_type' => ['sometimes', 'string', Rule::in(['indoor', 'outdoor', 'mixed'])],
            'phone' => ['sometimes', 'nullable', 'string', 'max:25'],
            'website_url' => ['sometimes', 'nullable', 'url'],
            'instagram_url' => ['sometimes', 'nullable', 'url'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'inactive'])],
            'is_featured' => ['sometimes', 'boolean'],
            'last_verified_at' => ['sometimes', 'nullable', 'date'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('trp_categories', 'id')->whereNull('deleted_at'),
            ],
            'facilities' => ['sometimes', 'array'],
            'facilities.*.facility_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('trp_facilities', 'id')->whereNull('deleted_at'),
            ],
            'facilities.*.notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opening_hours' => ['sometimes', 'array'],
            'opening_hours.*.day_of_week' => ['required', 'string', Rule::in(self::DAYS)],
            'opening_hours.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'opening_hours.*.open_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'opening_hours.*.close_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'opening_hours.*.is_closed' => ['sometimes', 'boolean'],
            'opening_hours.*.notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images' => ['sometimes', 'array'],
            'images.*.image_url' => ['required', 'url'],
            'images.*.public_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images.*.caption' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images.*.alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
            'images.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('short_description', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%")
                    ->orWhere('province', 'ilike', "%{$search}%");
            });
        }

        foreach (['status', 'place_type'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['city', 'province'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'ilike', $filters[$field]);
            }
        }

        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        if (! empty($filters['category'])) {
            $values = $this->splitValues($filters['category']);
            $ids = array_values(array_filter($values, 'is_numeric'));
            $slugs = array_values(array_diff($values, $ids));

            $query->whereHas('categories', function (Builder $query) use ($ids, $slugs): void {
                $query->where(function (Builder $query) use ($ids, $slugs): void {
                    if ($slugs !== []) {
                        $query->whereIn('slug', $slugs);
                    }

                    if ($ids !== []) {
                        $query->orWhereIn('trp_categories.id', $ids);
                    }
                });
            });
        }

        if (! empty($filters['facility'])) {
            $values = $this->splitValues($filters['facility']);
            $ids = array_values(array_filter($values, 'is_numeric'));
            $slugs = array_values(array_diff($values, $ids));

            $query->whereHas('facilities', function (Builder $query) use ($ids, $slugs): void {
                $query->where(function (Builder $query) use ($ids, $slugs): void {
                    if ($slugs !== []) {
                        $query->whereIn('slug', $slugs);
                    }

                    if ($ids !== []) {
                        $query->orWhereIn('trp_facilities.id', $ids);
                    }
                });
            });
        }
    }

    private function applySort(Builder $query, array $filters): void
    {
        match ($filters['sort'] ?? null) {
            'name' => $query->orderBy('name'),
            'updated' => $query->latest('updated_at'),
            'price_low' => $query->orderBy('ticket_price')->orderBy('name'),
            'price_high' => $query->orderByDesc('ticket_price')->orderBy('name'),
            default => $query->latest('id'),
        };
    }

    private function placeAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name',
            'slug',
            'short_description',
            'description',
            'address',
            'city',
            'province',
            'postal_code',
            'latitude',
            'longitude',
            'ticket_price',
            'parking_price_motorcycle',
            'parking_price_car',
            'recommended_duration_minutes',
            'place_type',
            'phone',
            'website_url',
            'instagram_url',
            'status',
            'is_featured',
            'last_verified_at',
        ]));
    }

    private function facilitySyncData(array $facilities): array
    {
        $syncData = [];

        foreach ($facilities as $facility) {
            $syncData[$facility['facility_id']] = [
                'notes' => $facility['notes'] ?? null,
            ];
        }

        return $syncData;
    }

    private function replaceOpeningHours(Place $place, array $openingHours): void
    {
        $place->openingHours()->delete();

        foreach ($openingHours as $index => $openingHour) {
            $isClosed = (bool) ($openingHour['is_closed'] ?? false);

            $place->openingHours()->create([
                'day_of_week' => $openingHour['day_of_week'],
                'sort_order' => $openingHour['sort_order'] ?? $index,
                'open_time' => $isClosed ? null : ($openingHour['open_time'] ?? null),
                'close_time' => $isClosed ? null : ($openingHour['close_time'] ?? null),
                'is_closed' => $isClosed,
                'notes' => $openingHour['notes'] ?? null,
            ]);
        }
    }

    private function replaceImages(Place $place, array $images, int $userId): void
    {
        $place->images()->delete();

        $hasPrimary = false;

        foreach ($images as $index => $image) {
            $isPrimary = (bool) ($image['is_primary'] ?? false) && ! $hasPrimary;
            $hasPrimary = $hasPrimary || $isPrimary;

            $place->images()->create([
                'image_url' => $image['image_url'],
                'public_id' => $image['public_id'] ?? null,
                'caption' => $image['caption'] ?? null,
                'alt_text' => $image['alt_text'] ?? null,
                'is_primary' => $isPrimary,
                'sort_order' => $image['sort_order'] ?? $index,
                'uploaded_by' => $userId,
            ]);
        }

        if (! $hasPrimary) {
            $place->images()->orderBy('sort_order')->orderBy('id')->first()?->update([
                'is_primary' => true,
            ]);
        }
    }

    private function loadPlace(Place $place): Place
    {
        return $place->load([
            'categories' => fn ($query) => $query->orderBy('name'),
            'facilities' => fn ($query) => $query->orderBy('name'),
            'openingHours' => fn ($query) => $query
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
                ->orderBy('sort_order'),
            'images',
            'primaryImage',
            'creator',
            'updater',
        ])->loadCount(['categories', 'facilities', 'openingHours', 'images', 'favorites', 'itineraryItems']);
    }

    private function splitValues(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value)
        )));
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
