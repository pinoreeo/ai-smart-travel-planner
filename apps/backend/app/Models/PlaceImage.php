<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceImage extends Model
{
    use HasFactory;

    protected $table = 'trp_place_images';

    protected $fillable = [
        'place_id',
        'image_url',
        'public_id',
        'caption',
        'alt_text',
        'is_primary',
        'sort_order',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(
            Place::class,
            'place_id'
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}
