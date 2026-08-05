<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryItem extends Model
{
    use HasFactory;

    protected $table = 'trp_itinerary_items';

    protected $fillable = [
        'itinerary_id',
        'place_id',
        'sequence',
        'travel_start_time',
        'arrival_time',
        'visit_start_time',
        'visit_end_time',
        'departure_time',
        'travel_duration_minutes',
        'visit_duration_minutes',
        'distance_km',
        'ticket_cost',
        'parking_cost',
        'transport_cost',
        'food_cost',
        'subtotal_cost',
        'route_geometry',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'travel_duration_minutes' => 'integer',
            'visit_duration_minutes' => 'integer',
            'distance_km' => 'decimal:2',
            'ticket_cost' => 'decimal:2',
            'parking_cost' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'food_cost' => 'decimal:2',
            'subtotal_cost' => 'decimal:2',
            'route_geometry' => 'array',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(
            Itinerary::class,
            'itinerary_id'
        );
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(
            Place::class,
            'place_id'
        );
    }
}
