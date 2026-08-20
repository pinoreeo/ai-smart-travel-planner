<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'favorited_at' => $this->created_at?->toISOString(),
            'place' => $this->whenLoaded(
                'place',
                fn () => (new PlaceCardResource($this->place))->resolve($request)
            ),
        ];
    }
}
