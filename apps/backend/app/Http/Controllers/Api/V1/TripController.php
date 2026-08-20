<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BudgetSummaryResource;
use App\Http\Resources\Api\V1\ItineraryItemResource;
use App\Http\Resources\Api\V1\ItineraryResource;
use App\Models\Category;
use App\Models\Itinerary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TripController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::in(['draft', 'generated', 'saved', 'completed', 'cancelled', 'upcoming', 'past'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Itinerary::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'items.place.primaryImage',
                'items.place.categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            ]);

        match ($filters['status'] ?? null) {
            'upcoming' => $query->whereIn('status', ['generated', 'saved'])->whereDate('travel_date', '>=', now()->toDateString()),
            'past' => $query->whereDate('travel_date', '<', now()->toDateString()),
            null => $query,
            default => $query->where('status', $filters['status']),
        };

        $trips = $query
            ->orderByRaw('travel_date is null')
            ->orderBy('travel_date')
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $trips,
            ItineraryResource::class,
            'Trips retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedTripData($request, partial: false);

        $itinerary = Itinerary::create([
            ...$this->tripAttributes($data),
            'user_id' => $request->user()->id,
            'generation_type' => $data['generation_type'] ?? 'manual',
            'status' => $data['status'] ?? 'draft',
        ]);

        $this->syncCategories($itinerary, $data);

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Trip created successfully.',
            201
        );
    }

    public function show(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Trip detail retrieved successfully.'
        );
    }

    public function update(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $data = $this->validatedTripData($request, partial: true);

        $itinerary->update($this->tripAttributes($data));
        $this->syncCategories($itinerary, $data);

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Trip updated successfully.'
        );
    }

    public function destroy(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $itinerary->delete();

        return $this->success(null, 'Trip deleted successfully.');
    }

    public function save(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $itinerary->update(['status' => 'saved']);

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Trip saved successfully.'
        );
    }

    public function timeline(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $items = $itinerary->items()
            ->with([
                'place.categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'place.primaryImage',
            ])
            ->get();

        return $this->success(
            ItineraryItemResource::collection($items)->resolve($request),
            'Trip timeline retrieved successfully.'
        );
    }

    public function map(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $itinerary->load(['items.place']);

        return $this->success([
            'start' => [
                'address' => $itinerary->start_address,
                'latitude' => $itinerary->start_latitude !== null ? (float) $itinerary->start_latitude : null,
                'longitude' => $itinerary->start_longitude !== null ? (float) $itinerary->start_longitude : null,
            ],
            'points' => $itinerary->items->map(fn ($item): array => [
                'sequence' => $item->sequence,
                'place_id' => $item->place_id,
                'name' => $item->place?->name,
                'latitude' => $item->place ? (float) $item->place->latitude : null,
                'longitude' => $item->place ? (float) $item->place->longitude : null,
                'arrival_time' => is_string($item->arrival_time) ? substr($item->arrival_time, 0, 5) : $item->arrival_time,
                'departure_time' => is_string($item->departure_time) ? substr($item->departure_time, 0, 5) : $item->departure_time,
                'route_geometry' => $item->route_geometry,
            ])->values(),
        ], 'Trip map retrieved successfully.');
    }

    public function budget(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        return $this->success(
            (new BudgetSummaryResource($itinerary))->resolve($request),
            'Trip budget retrieved successfully.'
        );
    }

    private function validatedTripData(Request $request, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:150'],
            'travel_date' => ['sometimes', 'nullable', 'date'],
            'start_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'end_time' => ['sometimes', 'nullable', 'date_format:H:i', 'after:start_time'],
            'start_address' => ['sometimes', 'nullable', 'string'],
            'start_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'start_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'number_of_people' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'transport_mode' => ['sometimes', 'nullable', Rule::in(['walking', 'motorcycle', 'car', 'cycling'])],
            'travel_style' => ['sometimes', Rule::in(['relaxed', 'balanced', 'packed'])],
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'generation_type' => ['sometimes', Rule::in(['manual', 'automatic', 'ai_assisted'])],
            'status' => ['sometimes', Rule::in(['draft', 'generated', 'saved', 'completed', 'cancelled'])],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:trp_categories,id'],
            'category_slugs' => ['sometimes', 'array'],
            'category_slugs.*' => ['string', 'exists:trp_categories,slug'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
    }

    private function tripAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'title',
            'travel_date',
            'start_time',
            'end_time',
            'start_address',
            'start_latitude',
            'start_longitude',
            'number_of_people',
            'transport_mode',
            'travel_style',
            'budget',
            'generation_type',
            'status',
            'notes',
        ]));
    }

    private function syncCategories(Itinerary $itinerary, array $data): void
    {
        if (! array_key_exists('category_ids', $data) && ! array_key_exists('category_slugs', $data)) {
            return;
        }

        $categoryIds = $data['category_ids'] ?? [];

        if (! empty($data['category_slugs'])) {
            $categoryIds = [
                ...$categoryIds,
                ...Category::query()->whereIn('slug', $data['category_slugs'])->pluck('id')->all(),
            ];
        }

        $itinerary->categories()->sync(array_values(array_unique($categoryIds)));
    }

    private function loadItinerary(Itinerary $itinerary): Itinerary
    {
        return $itinerary->load([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'items.place.categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'items.place.primaryImage',
        ]);
    }

    private function abortUnlessOwnedByUser(Itinerary $itinerary, int $userId): void
    {
        abort_if($itinerary->user_id !== $userId, 404);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 10), 1), 50);
    }
}
