<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'city' => $this->city,
            'province' => $this->province,
            'place_type' => $this->place_type,
            'ticket_price' => (float) $this->ticket_price,
            'recommended_duration_minutes' => $this->recommended_duration_minutes,
            'is_featured' => $this->is_featured,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'distance_km' => $this->when($this->hasDistance(), round((float) $this->distance_km, 2)),
            'categories' => CategoryResource::collection($this->whenLoaded('categories'))->resolve($request),
            'primary_image' => $this->whenLoaded(
                'primaryImage',
                fn () => $this->primaryImage ? (new PlaceImageResource($this->primaryImage))->resolve($request) : null
            ),
        ];
    }

    private function hasDistance(): bool
    {
        return array_key_exists('distance_km', $this->resource->getAttributes());
    }
}
