<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trp_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(
            Place::class,
            'trp_place_categories',
            'category_id',
            'place_id'
        )->withTimestamps();
    }

    public function itineraries(): BelongsToMany
    {
        return $this->belongsToMany(
            Itinerary::class,
            'trp_itinerary_categories',
            'category_id',
            'itinerary_id'
        )->withTimestamps();
    }
}
