<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_itinerary_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('itinerary_id')
                ->constrained('trp_itineraries')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('trp_categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['itinerary_id', 'category_id'],
                'trp_itinerary_categories_itinerary_category_unique'
            );

            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_itinerary_categories');
    }
};
