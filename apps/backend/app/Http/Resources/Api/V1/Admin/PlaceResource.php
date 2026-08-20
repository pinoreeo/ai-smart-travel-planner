<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'ticket_price' => (float) $this->ticket_price,
            'parking_price_motorcycle' => (float) $this->parking_price_motorcycle,
            'parking_price_car' => (float) $this->parking_price_car,
            'recommended_duration_minutes' => $this->recommended_duration_minutes,
            'place_type' => $this->place_type,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'instagram_url' => $this->instagram_url,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'is_active' => $category->is_active,
            ])->values()),
            'facilities' => $this->whenLoaded('facilities', fn () => $this->facilities->map(fn ($facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'icon' => $facility->icon,
                'is_active' => $facility->is_active,
                'notes' => $facility->pivot?->notes,
            ])->values()),
            'opening_hours' => $this->whenLoaded(
                'openingHours',
                fn () => PlaceOpeningHourResource::collection($this->openingHours)->resolve($request)
            ),
            'images' => $this->whenLoaded(
                'images',
                fn () => PlaceImageResource::collection($this->images)->resolve($request)
            ),
            'primary_image' => $this->whenLoaded(
                'primaryImage',
                fn () => $this->primaryImage ? (new PlaceImageResource($this->primaryImage))->resolve($request) : null
            ),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ] : null),
            'updater' => $this->whenLoaded('updater', fn () => $this->updater ? [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
                'email' => $this->updater->email,
            ] : null),
            'categories_count' => $this->whenCounted('categories'),
            'facilities_count' => $this->whenCounted('facilities'),
            'opening_hours_count' => $this->whenCounted('openingHours'),
            'images_count' => $this->whenCounted('images'),
            'favorites_count' => $this->whenCounted('favorites'),
            'itinerary_items_count' => $this->whenCounted('itineraryItems'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
