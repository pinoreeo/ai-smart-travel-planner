<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PlaceDetailResource extends PlaceCardResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'parking_price_motorcycle' => (float) $this->parking_price_motorcycle,
            'parking_price_car' => (float) $this->parking_price_car,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'instagram_url' => $this->instagram_url,
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities'))->resolve($request),
            'opening_hours' => OpeningHourResource::collection($this->whenLoaded('openingHours'))->resolve($request),
            'images' => PlaceImageResource::collection($this->whenLoaded('images'))->resolve($request),
        ];
    }
}
