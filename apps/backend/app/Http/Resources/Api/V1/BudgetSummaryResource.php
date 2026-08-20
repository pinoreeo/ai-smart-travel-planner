<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'budget' => (float) $this->budget,
            'tickets' => (float) $this->total_ticket_cost,
            'parking' => (float) $this->total_parking_cost,
            'transportation' => (float) $this->total_transport_cost,
            'food_estimate' => (float) $this->total_food_cost,
            'total' => (float) $this->total_cost,
            'remaining' => (float) $this->remaining_budget,
        ];
    }
}
