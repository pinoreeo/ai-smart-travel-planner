<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_place_facilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('place_id')
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('facility_id')
                ->constrained('trp_facilities')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->unique(
                ['place_id', 'facility_id'],
                'trp_place_facilities_place_facility_unique'
            );

            $table->index('facility_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_place_facilities');
    }
};
