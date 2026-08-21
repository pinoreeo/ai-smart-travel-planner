<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceImport extends Model
{
    use HasFactory;

    protected $table = 'trp_place_imports';

    protected $fillable = [
        'source',
        'source_id',
        'source_url',
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
        'image_url',
        'category_slugs',
        'facility_slugs',
        'opening_hours',
        'raw_payload',
        'quality_warnings',
        'status',
        'matched_place_id',
        'imported_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
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
            'category_slugs' => 'array',
            'facility_slugs' => 'array',
            'opening_hours' => 'array',
            'raw_payload' => 'array',
            'quality_warnings' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function matchedPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'matched_place_id');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
