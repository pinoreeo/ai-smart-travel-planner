<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trp_places';

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'ticket_price',
        'parking_price_motorcycle',
        'parking_price_car',
        'recommended_duration_minutes',
        'place_type',
        'phone',
        'website_url',
        'instagram_url',
        'status',
        'is_featured',
        'last_verified_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'ticket_price' => 'decimal:2',
            'parking_price_motorcycle' => 'decimal:2',
            'parking_price_car' => 'decimal:2',
            'recommended_duration_minutes' => 'integer',
            'is_featured' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'trp_place_categories',
            'place_id',
            'category_id'
        )->withTimestamps();
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Facility::class,
            'trp_place_facilities',
            'place_id',
            'facility_id'
        )
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(
            PlaceOpeningHour::class,
            'place_id'
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(
            PlaceImage::class,
            'place_id'
        )->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(
            PlaceImage::class,
            'place_id'
        )->where('is_primary', true);
    }

    public function itineraryItems(): HasMany
    {
        return $this->hasMany(
            ItineraryItem::class,
            'place_id'
        );
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(
            Favorite::class,
            'place_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
