<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trp_facilities', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trp_facilities');
    }
};
