<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_place_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('place_id')
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('trp_categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['place_id', 'category_id'],
                'trp_place_categories_place_category_unique'
            );

            /*
             * Unique composite di atas membantu pencarian yang dimulai
             * dari place_id. Index ini membantu pencarian category_id.
             */
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_place_categories');
    }
};
