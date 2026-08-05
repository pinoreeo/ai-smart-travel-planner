<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceOpeningHour extends Model
{
    use HasFactory;

    protected $table = 'trp_place_opening_hours';

    protected $fillable = [
        'place_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'open_time' => 'datetime:H:i',
            'close_time' => 'datetime:H:i',
            'is_closed' => 'boolean',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(
            Place::class,
            'place_id'
        );
    }
}
