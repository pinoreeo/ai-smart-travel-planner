<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('place_id')
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['user_id', 'place_id'],
                'trp_favorites_user_place_unique'
            );

            $table->index('place_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_favorites');
    }
};
