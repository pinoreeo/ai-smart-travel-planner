<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use DateTimeInterface;

class ItineraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'travel_date' => $this->travel_date?->toDateString(),
            'start_time' => $this->formatTime($this->start_time),
            'end_time' => $this->formatTime($this->end_time),
            'start_address' => $this->start_address,
            'start_latitude' => $this->start_latitude !== null ? (float) $this->start_latitude : null,
            'start_longitude' => $this->start_longitude !== null ? (float) $this->start_longitude : null,
            'number_of_people' => $this->number_of_people,
            'transport_mode' => $this->transport_mode,
            'travel_style' => $this->travel_style,
            'generation_type' => $this->generation_type,
            'status' => $this->status,
            'total_distance_km' => (float) $this->total_distance_km,
            'total_duration_minutes' => $this->total_duration_minutes,
            'notes' => $this->notes,
            'budget_summary' => (new BudgetSummaryResource($this->resource))->resolve($request),
            'categories' => CategoryResource::collection($this->whenLoaded('categories'))->resolve($request),
            'items' => ItineraryItemResource::collection($this->whenLoaded('items'))->resolve($request),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
