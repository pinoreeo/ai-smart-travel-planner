<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_id' => $this->source_id,
            'source_url' => $this->source_url,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'ticket_price' => $this->ticket_price !== null ? (float) $this->ticket_price : null,
            'parking_price_motorcycle' => $this->parking_price_motorcycle !== null ? (float) $this->parking_price_motorcycle : null,
            'parking_price_car' => $this->parking_price_car !== null ? (float) $this->parking_price_car : null,
            'recommended_duration_minutes' => $this->recommended_duration_minutes,
            'place_type' => $this->place_type,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'instagram_url' => $this->instagram_url,
            'image_url' => $this->image_url,
            'category_slugs' => $this->category_slugs ?? [],
            'facility_slugs' => $this->facility_slugs ?? [],
            'opening_hours' => $this->opening_hours,
            'raw_payload' => $this->raw_payload,
            'quality_warnings' => $this->quality_warnings ?? [],
            'status' => $this->status,
            'matched_place_id' => $this->matched_place_id,
            'matched_place' => $this->whenLoaded('matchedPlace', fn () => $this->matchedPlace ? [
                'id' => $this->matchedPlace->id,
                'name' => $this->matchedPlace->name,
                'slug' => $this->matchedPlace->slug,
                'status' => $this->matchedPlace->status,
            ] : null),
            'importer' => $this->whenLoaded('importer', fn () => $this->importer ? [
                'id' => $this->importer->id,
                'name' => $this->importer->name,
                'email' => $this->importer->email,
            ] : null),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ] : null),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_notes' => $this->review_notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
