<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trp_facilities';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
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
            'trp_place_facilities',
            'facility_id',
            'place_id'
        )
            ->withPivot('notes')
            ->withTimestamps();
    }
}
