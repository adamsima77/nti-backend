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
        Schema::create('study_field_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->foreignId('study_field_id')->constrained('study_field');
            $table->foreignId('language_id')->constrained('languages');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_field_translations');
    }
};
