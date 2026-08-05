<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_itineraries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title', 150);

            $table->date('travel_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->text('start_address')->nullable();
            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);

            $table->unsignedInteger('number_of_people')->default(1);

            /*
             * Nilai:
             * walking, motorcycle, car, cycling
             */
            $table->string('transport_mode', 20);

            /*
             * Nilai:
             * relaxed, balanced, packed
             */
            $table->string('travel_style', 20)->default('balanced');

            $table->decimal('budget', 15, 2)->default(0);

            $table->decimal('total_ticket_cost', 15, 2)->default(0);
            $table->decimal('total_parking_cost', 15, 2)->default(0);
            $table->decimal('total_transport_cost', 15, 2)->default(0);
            $table->decimal('total_food_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('remaining_budget', 15, 2)->default(0);

            $table->decimal('total_distance_km', 10, 2)->default(0);
            $table->unsignedInteger('total_duration_minutes')->default(0);

            /*
             * Nilai:
             * manual, automatic, ai_assisted
             */
            $table->string('generation_type', 20)->default('manual');

            /*
             * Nilai:
             * draft, generated, saved, completed, cancelled
             */
            $table->string('status', 20)->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('travel_date');
            $table->index('status');
            $table->index('generation_type');
            $table->index(['user_id', 'travel_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_itineraries');
    }
};
