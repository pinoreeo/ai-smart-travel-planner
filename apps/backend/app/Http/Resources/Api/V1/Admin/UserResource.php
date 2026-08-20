<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->map(fn ($role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_active' => $role->is_active,
                ])->values()
            ),
            'itineraries_count' => $this->whenCounted('itineraries'),
            'favorites_count' => $this->whenCounted('favorites'),
            'ai_requests_count' => $this->whenCounted('aiRequests'),
            'created_places_count' => $this->whenCounted('createdPlaces'),
            'uploaded_place_images_count' => $this->whenCounted('uploadedPlaceImages'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
