<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FavoriteResource;
use App\Http\Resources\Api\V1\PlaceCardResource;
use App\Models\Favorite;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'place' => fn ($query) => $query
                    ->select('trp_places.*')
                    ->where('status', 'published')
                    ->withExists([
                        'favorites as is_favorite' => fn ($query) => $query->where('user_id', $request->user()->id),
                    ]),
                'place.categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'place.primaryImage',
            ])
            ->whereHas('place', fn ($query) => $query->where('status', 'published'))
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            $favorites,
            FavoriteResource::class,
            'Favorites retrieved successfully.'
        );
    }

    public function store(Request $request, Place $place): JsonResponse
    {
        abort_if($place->status !== 'published', 404);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'place_id' => $place->id,
        ]);

        $place->setAttribute('is_favorite', true);
        $place->load([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'primaryImage',
        ]);

        return $this->success(
            [
                'favorite_id' => $favorite->id,
                'is_favorite' => true,
                'place' => (new PlaceCardResource($place))->resolve($request),
            ],
            'Place added to favorites.',
            $favorite->wasRecentlyCreated ? 201 : 200
        );
    }

    public function destroy(Request $request, Place $place): JsonResponse
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('place_id', $place->id)
            ->delete();

        $place->setAttribute('is_favorite', false);
        $place->load([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'primaryImage',
        ]);

        return $this->success(
            [
                'is_favorite' => false,
                'place' => (new PlaceCardResource($place))->resolve($request),
            ],
            'Place removed from favorites.'
        );
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 10), 1), 50);
    }
}
