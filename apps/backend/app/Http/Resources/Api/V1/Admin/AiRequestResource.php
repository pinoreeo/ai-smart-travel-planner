<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'itinerary_id' => $this->itinerary_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'itinerary' => $this->whenLoaded('itinerary', fn () => $this->itinerary ? [
                'id' => $this->itinerary->id,
                'title' => $this->itinerary->title,
                'status' => $this->itinerary->status,
                'travel_date' => $this->itinerary->travel_date?->toDateString(),
            ] : null),
            'request_type' => $this->request_type,
            'user_prompt' => $this->user_prompt,
            'parsed_parameters' => $this->parsed_parameters,
            'request_metadata' => $this->request_metadata,
            'model_name' => $this->model_name,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'input_tokens' => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'total_tokens' => ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0),
            'processing_time_ms' => $this->processing_time_ms,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
