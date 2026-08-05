<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_ai_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('itinerary_id')
                ->nullable()
                ->constrained('trp_itineraries')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
             * Nilai:
             * parse_trip_request,
             * modify_itinerary,
             * explain_itinerary
             */
            $table->string('request_type', 50);

            $table->text('user_prompt');

            /*
             * Hasil parsing AI, misalnya:
             * category, budget, waktu, transportasi, dan preferensi.
             */
            $table->jsonb('parsed_parameters')->nullable();

            /*
             * Metadata tambahan yang aman disimpan.
             * Jangan menyimpan API key.
             */
            $table->jsonb('request_metadata')->nullable();

            $table->string('model_name', 100)->nullable();

            /*
             * Nilai:
             * pending, processing, success, failed, timeout
             */
            $table->string('status', 20)->default('pending');

            $table->text('error_message')->nullable();

            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('itinerary_id');
            $table->index('request_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_ai_requests');
    }
};
