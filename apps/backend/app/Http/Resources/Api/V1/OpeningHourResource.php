<?php

namespace App\Http\Resources\Api\V1;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpeningHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'sort_order' => $this->sort_order,
            'open_time' => $this->formatTime($this->open_time),
            'close_time' => $this->formatTime($this->close_time),
            'is_closed' => $this->is_closed,
            'notes' => $this->notes,
        ];
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        return $value;
    }
}
