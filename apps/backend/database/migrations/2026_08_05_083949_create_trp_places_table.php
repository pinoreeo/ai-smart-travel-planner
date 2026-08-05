<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_places', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('slug', 180)->unique();

            $table->string('short_description', 255)->nullable();
            $table->text('description')->nullable();

            $table->text('address');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 15)->nullable();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->decimal('ticket_price', 15, 2)->default(0);
            $table->decimal('parking_price_motorcycle', 15, 2)->default(0);
            $table->decimal('parking_price_car', 15, 2)->default(0);

            $table->unsignedInteger('recommended_duration_minutes')
                ->default(60);

            /*
             * Nilai:
             * indoor, outdoor, mixed
             */
            $table->string('place_type', 20)->default('outdoor');

            $table->string('phone', 25)->nullable();
            $table->text('website_url')->nullable();
            $table->text('instagram_url')->nullable();

            /*
             * Nilai:
             * draft, published, inactive
             */
            $table->string('status', 20)->default('draft');

            $table->boolean('is_featured')->default(false);
            $table->timestamp('last_verified_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('city');
            $table->index('province');
            $table->index('status');
            $table->index('is_featured');
            $table->index('ticket_price');
            $table->index(['latitude', 'longitude']);

            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_places');
    }
};
