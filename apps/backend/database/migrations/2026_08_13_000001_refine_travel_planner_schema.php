<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trp_itineraries', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->change();
            $table->date('travel_date')->nullable()->change();
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            $table->decimal('start_latitude', 10, 7)->nullable()->change();
            $table->decimal('start_longitude', 10, 7)->nullable()->change();
            $table->string('transport_mode', 20)->nullable()->change();
        });

        Schema::table('trp_itinerary_items', function (Blueprint $table) {
            $table->dropUnique('trp_itinerary_items_itinerary_place_unique');
        });

        Schema::table('trp_place_opening_hours', function (Blueprint $table) {
            $table->dropUnique('trp_opening_hours_place_day_unique');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(
                ['place_id', 'day_of_week', 'sort_order'],
                'trp_opening_hours_place_day_sort_unique'
            );
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS trp_place_images_primary_unique ' .
                'ON trp_place_images (place_id) WHERE is_primary = true'
            );
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS trp_place_images_primary_unique');
        }

        Schema::table('trp_place_opening_hours', function (Blueprint $table) {
            $table->dropUnique('trp_opening_hours_place_day_sort_unique');
            $table->dropColumn('sort_order');
            $table->unique(
                ['place_id', 'day_of_week'],
                'trp_opening_hours_place_day_unique'
            );
        });

        Schema::table('trp_itinerary_items', function (Blueprint $table) {
            $table->unique(
                ['itinerary_id', 'place_id'],
                'trp_itinerary_items_itinerary_place_unique'
            );
        });

        Schema::table('trp_itineraries', function (Blueprint $table) {
            $table->string('title', 150)->nullable(false)->change();
            $table->date('travel_date')->nullable(false)->change();
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
            $table->decimal('start_latitude', 10, 7)->nullable(false)->change();
            $table->decimal('start_longitude', 10, 7)->nullable(false)->change();
            $table->string('transport_mode', 20)->nullable(false)->change();
        });
    }
};
