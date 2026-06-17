<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hero_banner_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hero_banner_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('language_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['hero_banner_id', 'language_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_banner_translations');
    }
};
