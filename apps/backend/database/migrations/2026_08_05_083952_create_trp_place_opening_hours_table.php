<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_place_opening_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('place_id')
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Nilai:
             * monday, tuesday, wednesday, thursday,
             * friday, saturday, sunday
             */
            $table->string('day_of_week', 10);

            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();

            $table->boolean('is_closed')->default(false);
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->unique(
                ['place_id', 'day_of_week'],
                'trp_opening_hours_place_day_unique'
            );

            $table->index('day_of_week');
            $table->index('is_closed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_place_opening_hours');
    }
};
