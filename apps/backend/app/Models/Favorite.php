<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    protected $table = 'trp_favorites';

    protected $fillable = [
        'user_id',
        'place_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
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
