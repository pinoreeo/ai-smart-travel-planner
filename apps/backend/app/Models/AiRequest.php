<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    use HasFactory;

    protected $table = 'trp_ai_requests';

    protected $fillable = [
        'user_id',
        'itinerary_id',
        'request_type',
        'user_prompt',
        'parsed_parameters',
        'request_metadata',
        'model_name',
        'status',
        'error_message',
        'input_tokens',
        'output_tokens',
        'processing_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'parsed_parameters' => 'array',
            'request_metadata' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'processing_time_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(
            Itinerary::class,
            'itinerary_id'
        );
    }
}
