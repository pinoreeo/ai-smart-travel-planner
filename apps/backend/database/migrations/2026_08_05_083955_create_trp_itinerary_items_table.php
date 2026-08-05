<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_itinerary_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('itinerary_id')
                ->constrained('trp_itineraries')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('place_id')
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('sequence');

            $table->time('travel_start_time')->nullable();
            $table->time('arrival_time');
            $table->time('visit_start_time');
            $table->time('visit_end_time');
            $table->time('departure_time')->nullable();

            $table->unsignedInteger('travel_duration_minutes')->default(0);
            $table->unsignedInteger('visit_duration_minutes')->default(0);

            $table->decimal('distance_km', 10, 2)->default(0);

            $table->decimal('ticket_cost', 15, 2)->default(0);
            $table->decimal('parking_cost', 15, 2)->default(0);
            $table->decimal('transport_cost', 15, 2)->default(0);
            $table->decimal('food_cost', 15, 2)->default(0);
            $table->decimal('subtotal_cost', 15, 2)->default(0);

            /*
             * Menyimpan GeoJSON atau data route dari routing provider.
             */
            $table->jsonb('route_geometry')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['itinerary_id', 'sequence'],
                'trp_itinerary_items_itinerary_sequence_unique'
            );

            $table->unique(
                ['itinerary_id', 'place_id'],
                'trp_itinerary_items_itinerary_place_unique'
            );

            $table->index('place_id');
            $table->index('arrival_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_itinerary_items');
    }
};
