<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ItineraryResource;
use App\Models\Category;
use App\Models\Itinerary;
use App\Models\Place;
use App\Services\Routing\RoutingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlannerController extends Controller
{
    use RespondsWithApiResponse;

    public function __construct(
        private readonly RoutingService $routing
    ) {}

    public function storeDraft(Request $request): JsonResponse
    {
        $data = $this->validatedPlannerData($request, partial: true);

        $itinerary = Itinerary::create([
            ...$this->itineraryAttributes($data),
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? 'Untitled Trip',
            'generation_type' => 'manual',
            'status' => 'draft',
        ]);

        $this->syncCategories($itinerary, $data);

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Planner draft created successfully.',
            201
        );
    }

    public function updateDraft(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $data = $this->validatedPlannerData($request, partial: true);

        $itinerary->update($this->itineraryAttributes($data));
        $this->syncCategories($itinerary, $data);

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Planner draft updated successfully.'
        );
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $this->validatedPlannerData($request, partial: false);
        $userId = $request->user()->id;

        $itinerary = DB::transaction(function () use ($data, $userId): Itinerary {
            $itinerary = isset($data['itinerary_id'])
                ? Itinerary::query()->where('user_id', $userId)->findOrFail($data['itinerary_id'])
                : new Itinerary(['user_id' => $userId]);

            $itinerary->fill([
                ...$this->itineraryAttributes($data),
                'title' => $data['title'] ?? 'Explore Banyumas',
                'generation_type' => 'automatic',
                'status' => 'generated',
            ]);
            $itinerary->save();

            $this->syncCategories($itinerary, $data);
            $this->buildItineraryItems($itinerary, $data);

            return $itinerary;
        });

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Itinerary generated successfully.'
        );
    }

    public function modify(Request $request, Itinerary $itinerary): JsonResponse
    {
        $this->abortUnlessOwnedByUser($itinerary, $request->user()->id);

        $data = $this->validatedPlannerData($request, partial: true);

        DB::transaction(function () use ($itinerary, $data): void {
            $itinerary->update([
                ...$this->itineraryAttributes($data),
                'status' => $itinerary->status === 'draft' ? 'generated' : $itinerary->status,
            ]);

            $this->syncCategories($itinerary, $data);

            if (($data['regenerate'] ?? false) === true) {
                $this->buildItineraryItems($itinerary, $data);
            }
        });

        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Itinerary modified successfully.'
        );
    }

    private function validatedPlannerData(Request $request, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'itinerary_id' => ['sometimes', 'integer', 'exists:trp_itineraries,id'],
            'title' => ['sometimes', 'string', 'max:150'],
            'travel_date' => [$required, 'date'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i', 'after:start_time'],
            'start_address' => ['sometimes', 'nullable', 'string'],
            'start_latitude' => [$required, 'numeric', 'between:-90,90'],
            'start_longitude' => [$required, 'numeric', 'between:-180,180'],
            'number_of_people' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'transport_mode' => [$required, Rule::in(['walking', 'motorcycle', 'car', 'cycling'])],
            'travel_style' => ['sometimes', Rule::in(['relaxed', 'balanced', 'packed'])],
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:trp_categories,id'],
            'category_slugs' => ['sometimes', 'array'],
            'category_slugs.*' => ['string', 'exists:trp_categories,slug'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'regenerate' => ['sometimes', 'boolean'],
        ]);
    }

    private function itineraryAttributes(array $data): array
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

    private function buildItineraryItems(Itinerary $itinerary, array $data): void
    {
        $itinerary->items()->delete();

        $places = $this->candidatePlaces($itinerary, $data)->get();
        $currentLat = (float) $itinerary->start_latitude;
        $currentLng = (float) $itinerary->start_longitude;
        $cursor = Carbon::createFromFormat('H:i', $itinerary->start_time);
        $endTime = Carbon::createFromFormat('H:i', $itinerary->end_time);
        $people = $itinerary->number_of_people ?: 1;
        $budget = (float) $itinerary->budget;

        $totals = [
            'ticket' => 0.0,
            'parking' => 0.0,
            'transport' => 0.0,
            'food' => 0.0,
            'cost' => 0.0,
            'distance' => 0.0,
        ];
        $sequence = 1;

        foreach ($places as $place) {
            $route = $this->routing->route(
                $currentLat,
                $currentLng,
                (float) $place->latitude,
                (float) $place->longitude,
                $itinerary->transport_mode
            );
            $distance = $route->distanceKm;
            $travelDuration = $route->durationMinutes;
            $visitDuration = $this->visitDurationMinutes((int) $place->recommended_duration_minutes, $itinerary->travel_style);
            $arrival = $cursor->copy()->addMinutes($travelDuration);
            $departure = $arrival->copy()->addMinutes($visitDuration);

            if ($departure->greaterThan($endTime)) {
                continue;
            }

            $ticketCost = (float) $place->ticket_price * $people;
            $parkingCost = $this->parkingCost($place, $itinerary->transport_mode);
            $transportCost = $this->transportCost($distance, $itinerary->transport_mode);
            $foodCost = 30000 * $people;
            $subtotal = $ticketCost + $parkingCost + $transportCost + $foodCost;

            if ($budget > 0 && ($totals['cost'] + $subtotal) > $budget && $sequence > 1) {
                continue;
            }

            $itinerary->items()->create([
                'place_id' => $place->id,
                'sequence' => $sequence,
                'travel_start_time' => $cursor->format('H:i'),
                'arrival_time' => $arrival->format('H:i'),
                'visit_start_time' => $arrival->format('H:i'),
                'visit_end_time' => $departure->format('H:i'),
                'departure_time' => $departure->format('H:i'),
                'travel_duration_minutes' => $travelDuration,
                'visit_duration_minutes' => $visitDuration,
                'distance_km' => round($distance, 2),
                'ticket_cost' => $ticketCost,
                'parking_cost' => $parkingCost,
                'transport_cost' => $transportCost,
                'food_cost' => $foodCost,
                'subtotal_cost' => $subtotal,
                'route_geometry' => $route->geometry,
            ]);

            $totals['ticket'] += $ticketCost;
            $totals['parking'] += $parkingCost;
            $totals['transport'] += $transportCost;
            $totals['food'] += $foodCost;
            $totals['cost'] += $subtotal;
            $totals['distance'] += $distance;

            $currentLat = (float) $place->latitude;
            $currentLng = (float) $place->longitude;
            $cursor = $departure;
            $sequence++;
        }

        $itinerary->update([
            'total_ticket_cost' => $totals['ticket'],
            'total_parking_cost' => $totals['parking'],
            'total_transport_cost' => $totals['transport'],
            'total_food_cost' => $totals['food'],
            'total_cost' => $totals['cost'],
            'remaining_budget' => $budget - $totals['cost'],
            'total_distance_km' => round($totals['distance'], 2),
            'total_duration_minutes' => Carbon::createFromFormat('H:i', $itinerary->start_time)->diffInMinutes($cursor),
        ]);
    }

    private function candidatePlaces(Itinerary $itinerary, array $data): Builder
    {
        $categoryIds = $data['category_ids'] ?? $itinerary->categories()->pluck('trp_categories.id')->all();

        if (! empty($data['category_slugs'])) {
            $categoryIds = [
                ...$categoryIds,
                ...Category::query()->whereIn('slug', $data['category_slugs'])->pluck('id')->all(),
            ];
        }

        return Place::query()
            ->where('status', 'published')
            ->when($categoryIds !== [], function (Builder $query) use ($categoryIds): void {
                $query->whereHas('categories', fn (Builder $query) => $query->whereIn('trp_categories.id', array_unique($categoryIds)));
            })
            ->orderByDesc('is_featured')
            ->orderBy('ticket_price')
            ->orderBy('name')
            ->limit(8);
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

    private function visitDurationMinutes(int $recommendedDuration, string $travelStyle): int
    {
        return match ($travelStyle) {
            'relaxed' => (int) round($recommendedDuration * 1.2),
            'packed' => (int) round($recommendedDuration * 0.75),
            default => $recommendedDuration,
        };
    }

    private function transportCost(float $distanceKm, string $transportMode): float
    {
        $costPerKm = match ($transportMode) {
            'motorcycle' => 1000,
            'car' => 2500,
            default => 0,
        };

        return round($distanceKm * $costPerKm, 2);
    }

    private function parkingCost(Place $place, string $transportMode): float
    {
        return match ($transportMode) {
            'motorcycle' => (float) $place->parking_price_motorcycle,
            'car' => (float) $place->parking_price_car,
            default => 0,
        };
    }
}
