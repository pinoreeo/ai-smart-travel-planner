<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_place_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('place_id')
                ->constrained('trp_places')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('image_url');

            /*
             * Digunakan oleh Cloudinary atau storage provider lainnya.
             */
            $table->string('public_id', 255)->nullable();

            $table->string('caption', 255)->nullable();
            $table->string('alt_text', 255)->nullable();

            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();

            $table->index('place_id');
            $table->index('uploaded_by');
            $table->index('is_primary');
            $table->index(['place_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_place_images');
    }
};
