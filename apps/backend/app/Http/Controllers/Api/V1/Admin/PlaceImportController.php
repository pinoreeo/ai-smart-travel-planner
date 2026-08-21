<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\PlaceImportResource;
use App\Http\Resources\Api\V1\Admin\PlaceResource;
use App\Models\Category;
use App\Models\Facility;
use App\Models\Place;
use App\Models\PlaceImport;
use App\Services\PlaceImports\OverpassPlaceImportService;
use App\Services\PlaceImports\PlaceImportNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlaceImportController extends Controller
{
    use RespondsWithApiResponse;

    public function __construct(
        private readonly PlaceImportNormalizer $normalizer,
        private readonly OverpassPlaceImportService $overpass
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'source' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'approved', 'rejected', 'duplicate'])],
            'province' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = PlaceImport::query()
            ->with(['matchedPlace', 'importer', 'reviewer']);

        $this->applyFilters($query, $filters);

        $imports = $query
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $imports,
            PlaceImportResource::class,
            'Place imports retrieved successfully.'
        );
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'source' => ['sometimes', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['array'],
            'items.*.name' => ['required', 'string', 'max:150'],
        ]);

        $source = (string) $request->input('source', 'manual');
        $items = collect($request->input('items', []))
            ->map(fn (array $item): array => $this->normalizer->normalizeManual($item, $source))
            ->values()
            ->all();

        return $this->success([
            'items' => $items,
            'count' => count($items),
        ], 'Place import preview generated successfully.');
    }

    public function previewOsm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province' => ['sometimes', 'string', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $items = $this->overpass->preview(
            $data['province'] ?? 'Jawa Tengah',
            (int) ($data['limit'] ?? 50)
        );

        return $this->success([
            'source' => 'osm',
            'items' => $items,
            'count' => count($items),
        ], 'OSM place import preview generated successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'source' => ['sometimes', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['array'],
            'items.*.name' => ['required', 'string', 'max:150'],
        ]);

        $source = (string) $request->input('source', 'manual');
        $items = $request->input('items', []);
        $userId = $request->user()->id;
        $imports = DB::transaction(function () use ($items, $source, $userId) {
            return collect($items)
                ->map(fn (array $item): PlaceImport => $this->storeImport(
                    $this->normalizer->normalizeManual($item, $source),
                    $userId
                ))
                ->values();
        });

        return $this->success(
            PlaceImportResource::collection($imports)->resolve($request),
            'Place imports stored successfully.',
            201
        );
    }

    public function show(Request $request, PlaceImport $placeImport): JsonResponse
    {
        return $this->success(
            (new PlaceImportResource($this->loadImport($placeImport)))->resolve($request),
            'Place import detail retrieved successfully.'
        );
    }

    public function update(Request $request, PlaceImport $placeImport): JsonResponse
    {
        abort_if($placeImport->status === 'approved', 422, 'Approved import cannot be edited.');

        $data = $request->validate($this->validationRules(partial: true));
        $normalized = $this->normalizer->normalizeManual([
            ...$placeImport->toArray(),
            ...$data,
        ], $placeImport->source);
        $matchedPlace = $this->matchedPlace($normalized);

        $placeImport->update([
            ...$this->importAttributes($normalized),
            'status' => $matchedPlace ? 'duplicate' : ($placeImport->status === 'duplicate' ? 'pending' : $placeImport->status),
            'matched_place_id' => $matchedPlace?->id,
        ]);

        return $this->success(
            (new PlaceImportResource($this->loadImport($placeImport)))->resolve($request),
            'Place import updated successfully.'
        );
    }

    public function destroy(PlaceImport $placeImport): JsonResponse
    {
        abort_if($placeImport->status === 'approved', 422, 'Approved import cannot be deleted.');

        $placeImport->delete();

        return $this->success(null, 'Place import deleted successfully.');
    }

    public function approve(Request $request, PlaceImport $placeImport): JsonResponse
    {
        $data = $request->validate([
            'publish' => ['sometimes', 'boolean'],
            'review_notes' => ['sometimes', 'nullable', 'string'],
        ]);

        abort_if($placeImport->status === 'approved', 422, 'Place import already approved.');
        abort_if($placeImport->matched_place_id !== null, 422, 'Place import matches an existing place.');

        $this->ensureReadyToApprove($placeImport);

        $place = DB::transaction(function () use ($data, $placeImport, $request): Place {
            $place = Place::create([
                'name' => $placeImport->name,
                'slug' => $placeImport->slug,
                'short_description' => $placeImport->short_description,
                'description' => $placeImport->description,
                'address' => $placeImport->address,
                'city' => $placeImport->city,
                'province' => $placeImport->province,
                'postal_code' => $placeImport->postal_code,
                'latitude' => $placeImport->latitude,
                'longitude' => $placeImport->longitude,
                'ticket_price' => $placeImport->ticket_price ?? 0,
                'parking_price_motorcycle' => $placeImport->parking_price_motorcycle ?? 0,
                'parking_price_car' => $placeImport->parking_price_car ?? 0,
                'recommended_duration_minutes' => $placeImport->recommended_duration_minutes ?? 60,
                'place_type' => $placeImport->place_type ?? 'outdoor',
                'phone' => $placeImport->phone,
                'website_url' => $placeImport->website_url,
                'instagram_url' => $placeImport->instagram_url,
                'status' => ($data['publish'] ?? false) ? 'published' : 'draft',
                'is_featured' => false,
                'last_verified_at' => ($data['publish'] ?? false) ? now() : null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->syncPlaceRelations($place, $placeImport, $request->user()->id);

            $placeImport->update([
                'status' => 'approved',
                'matched_place_id' => $place->id,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);

            return $place;
        });

        return $this->success([
            'import' => (new PlaceImportResource($this->loadImport($placeImport)))->resolve($request),
            'place' => (new PlaceResource($this->loadPlace($place)))->resolve($request),
        ], 'Place import approved successfully.');
    }

    public function reject(Request $request, PlaceImport $placeImport): JsonResponse
    {
        $data = $request->validate([
            'review_notes' => ['required', 'string'],
        ]);

        abort_if($placeImport->status === 'approved', 422, 'Approved import cannot be rejected.');

        $placeImport->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);

        return $this->success(
            (new PlaceImportResource($this->loadImport($placeImport)))->resolve($request),
            'Place import rejected successfully.'
        );
    }

    private function storeImport(array $item, int $userId): PlaceImport
    {
        $matchedPlace = $this->matchedPlace($item);
        $attributes = [
            ...$this->importAttributes($item),
            'status' => $matchedPlace ? 'duplicate' : 'pending',
            'matched_place_id' => $matchedPlace?->id,
            'imported_by' => $userId,
        ];

        if (! empty($item['source_id'])) {
            $import = PlaceImport::firstOrNew([
                'source' => $item['source'],
                'source_id' => $item['source_id'],
            ]);

            if ($import->exists && $import->status === 'approved') {
                return $import;
            }

            $import->fill($attributes);
            $import->save();

            return $import;
        }

        return PlaceImport::create($attributes);
    }

    private function matchedPlace(array $item): ?Place
    {
        if (empty($item['slug'])) {
            return null;
        }

        return Place::query()
            ->withTrashed()
            ->where('slug', $item['slug'])
            ->first();
    }

    private function ensureReadyToApprove(PlaceImport $placeImport): void
    {
        $missing = [];

        foreach (['name', 'slug', 'address', 'city', 'province', 'latitude', 'longitude'] as $field) {
            if ($placeImport->{$field} === null || $placeImport->{$field} === '') {
                $missing[] = $field;
            }
        }

        if (Place::query()->withTrashed()->where('slug', $placeImport->slug)->exists()) {
            $missing[] = 'slug sudah dipakai destinasi lain';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'place_import' => ['Lengkapi dulu sebelum approve: '.implode(', ', $missing).'.'],
            ]);
        }
    }

    private function syncPlaceRelations(Place $place, PlaceImport $placeImport, int $userId): void
    {
        $categoryIds = Category::query()
            ->whereIn('slug', $placeImport->category_slugs ?? [])
            ->pluck('id')
            ->all();

        $place->categories()->sync($categoryIds);

        $facilityIds = Facility::query()
            ->whereIn('slug', $placeImport->facility_slugs ?? [])
            ->pluck('id')
            ->all();

        $place->facilities()->sync(array_fill_keys($facilityIds, ['notes' => null]));

        if ($placeImport->image_url !== null) {
            $place->images()->create([
                'image_url' => $placeImport->image_url,
                'caption' => $placeImport->name,
                'alt_text' => $placeImport->name,
                'is_primary' => true,
                'sort_order' => 0,
                'uploaded_by' => $userId,
            ]);
        }
    }

    private function importAttributes(array $item): array
    {
        return array_intersect_key($item, array_flip([
            'source',
            'source_id',
            'source_url',
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
            'image_url',
            'category_slugs',
            'facility_slugs',
            'opening_hours',
            'raw_payload',
            'quality_warnings',
        ]));
    }

    private function validationRules(bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:150'],
            'slug' => ['sometimes', 'string', 'max:180'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:15'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'ticket_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'parking_price_motorcycle' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'parking_price_car' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'recommended_duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:15', 'max:1440'],
            'place_type' => ['sometimes', 'nullable', Rule::in(['indoor', 'outdoor', 'mixed'])],
            'phone' => ['sometimes', 'nullable', 'string', 'max:25'],
            'website_url' => ['sometimes', 'nullable', 'url'],
            'instagram_url' => ['sometimes', 'nullable', 'url'],
            'image_url' => ['sometimes', 'nullable', 'url'],
            'category_slugs' => ['sometimes', 'array'],
            'category_slugs.*' => ['string'],
            'facility_slugs' => ['sometimes', 'array'],
            'facility_slugs.*' => ['string'],
            'opening_hours' => ['sometimes', 'nullable', 'array'],
            'raw_payload' => ['sometimes', 'nullable', 'array'],
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%");
            });
        }

        foreach (['source', 'status', 'province', 'city'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $field === 'source' || $field === 'status' ? $filters[$field] : 'ilike', $filters[$field]);
            }
        }
    }

    private function loadImport(PlaceImport $placeImport): PlaceImport
    {
        return $placeImport->load(['matchedPlace', 'importer', 'reviewer']);
    }

    private function loadPlace(Place $place): Place
    {
        return $place->load([
            'categories' => fn ($query) => $query->orderBy('name'),
            'facilities' => fn ($query) => $query->orderBy('name'),
            'images',
            'primaryImage',
            'creator',
            'updater',
        ])->loadCount(['categories', 'facilities', 'openingHours', 'images', 'favorites', 'itineraryItems']);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
