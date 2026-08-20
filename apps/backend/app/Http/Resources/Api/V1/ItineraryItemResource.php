<?php

namespace App\Http\Resources\Api\V1;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItineraryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'travel_start_time' => $this->formatTime($this->travel_start_time),
            'arrival_time' => $this->formatTime($this->arrival_time),
            'visit_start_time' => $this->formatTime($this->visit_start_time),
            'visit_end_time' => $this->formatTime($this->visit_end_time),
            'departure_time' => $this->formatTime($this->departure_time),
            'travel_duration_minutes' => $this->travel_duration_minutes,
            'visit_duration_minutes' => $this->visit_duration_minutes,
            'distance_km' => (float) $this->distance_km,
            'ticket_cost' => (float) $this->ticket_cost,
            'parking_cost' => (float) $this->parking_cost,
            'transport_cost' => (float) $this->transport_cost,
            'food_cost' => (float) $this->food_cost,
            'subtotal_cost' => (float) $this->subtotal_cost,
            'route_geometry' => $this->route_geometry,
            'notes' => $this->notes,
            'place' => $this->whenLoaded(
                'place',
                fn () => (new PlaceCardResource($this->place))->resolve($request)
            ),
        ];
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        return is_string($value) ? substr($value, 0, 5) : $value;
    }
}
