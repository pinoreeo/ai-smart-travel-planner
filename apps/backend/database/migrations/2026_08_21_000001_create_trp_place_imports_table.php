<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_place_imports', function (Blueprint $table) {
            $table->id();

            $table->string('source', 50);
            $table->string('source_id', 150)->nullable();
            $table->text('source_url')->nullable();

            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('short_description', 255)->nullable();
            $table->text('description')->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 15)->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->decimal('ticket_price', 15, 2)->nullable();
            $table->decimal('parking_price_motorcycle', 15, 2)->nullable();
            $table->decimal('parking_price_car', 15, 2)->nullable();
            $table->unsignedInteger('recommended_duration_minutes')->nullable();
            $table->string('place_type', 20)->nullable();

            $table->string('phone', 25)->nullable();
            $table->text('website_url')->nullable();
            $table->text('instagram_url')->nullable();
            $table->text('image_url')->nullable();

            $table->jsonb('category_slugs')->nullable();
            $table->jsonb('facility_slugs')->nullable();
            $table->jsonb('opening_hours')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->jsonb('quality_warnings')->nullable();

            /*
             * Nilai:
             * pending, approved, rejected, duplicate
             */
            $table->string('status', 20)->default('pending');

            $table->foreignId('matched_place_id')
                ->nullable()
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('imported_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->unique(['source', 'source_id'], 'trp_place_imports_source_id_unique');
            $table->index('source');
            $table->index('slug');
            $table->index('status');
            $table->index('province');
            $table->index('city');
            $table->index('matched_place_id');
            $table->index('imported_by');
            $table->index('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_place_imports');
    }
};
