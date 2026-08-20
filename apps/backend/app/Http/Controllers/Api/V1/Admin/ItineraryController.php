<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\ItineraryResource;
use App\Models\Itinerary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItineraryController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'generated', 'saved', 'completed', 'cancelled'])],
            'generation_type' => ['sometimes', 'string', Rule::in(['manual', 'automatic', 'ai_assisted'])],
            'travel_date_from' => ['sometimes', 'date'],
            'travel_date_to' => ['sometimes', 'date', 'after_or_equal:travel_date_from'],
            'with_trashed' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Itinerary::query()
            ->with('user')
            ->withCount(['items', 'categories', 'aiRequests']);

        if (($filters['with_trashed'] ?? false) === true) {
            $query->withTrashed();
        }

        $this->applyFilters($query, $filters);

        $itineraries = $query
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $itineraries,
            ItineraryResource::class,
            'Admin itineraries retrieved successfully.'
        );
    }

    public function show(Request $request, Itinerary $itinerary): JsonResponse
    {
        return $this->success(
            (new ItineraryResource($this->loadItinerary($itinerary)))->resolve($request),
            'Admin itinerary detail retrieved successfully.'
        );
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('title', 'ilike', "%{$search}%")
                    ->orWhere('start_address', 'ilike', "%{$search}%")
                    ->orWhere('notes', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function (Builder $query) use ($search): void {
                        $query
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
            });
        }

        foreach (['user_id', 'status', 'generation_type'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['travel_date_from'])) {
            $query->whereDate('travel_date', '>=', $filters['travel_date_from']);
        }

        if (! empty($filters['travel_date_to'])) {
            $query->whereDate('travel_date', '<=', $filters['travel_date_to']);
        }
    }

    private function loadItinerary(Itinerary $itinerary): Itinerary
    {
        return $itinerary->load([
            'user',
            'categories' => fn ($query) => $query->orderBy('name'),
            'items.place.categories' => fn ($query) => $query->orderBy('name'),
            'items.place.primaryImage',
        ])->loadCount(['items', 'categories', 'aiRequests']);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
