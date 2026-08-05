<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Itinerary extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trp_itineraries';

    protected $fillable = [
        'user_id',
        'title',
        'travel_date',
        'start_time',
        'end_time',
        'start_address',
        'start_latitude',
        'start_longitude',
        'number_of_people',
        'transport_mode',
        'travel_style',
        'budget',
        'total_ticket_cost',
        'total_parking_cost',
        'total_transport_cost',
        'total_food_cost',
        'total_cost',
        'remaining_budget',
        'total_distance_km',
        'total_duration_minutes',
        'generation_type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'start_latitude' => 'decimal:7',
            'start_longitude' => 'decimal:7',
            'number_of_people' => 'integer',
            'budget' => 'decimal:2',
            'total_ticket_cost' => 'decimal:2',
            'total_parking_cost' => 'decimal:2',
            'total_transport_cost' => 'decimal:2',
            'total_food_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'remaining_budget' => 'decimal:2',
            'total_distance_km' => 'decimal:2',
            'total_duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            ItineraryItem::class,
            'itinerary_id'
        )->orderBy('sequence');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'trp_itinerary_categories',
            'itinerary_id',
            'category_id'
        )->withTimestamps();
    }

    public function aiRequests(): HasMany
    {
        return $this->hasMany(
            AiRequest::class,
            'itinerary_id'
        );
    }
}
