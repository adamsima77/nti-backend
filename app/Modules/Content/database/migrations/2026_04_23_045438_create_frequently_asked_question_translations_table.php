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
        Schema::create('frequently_asked_question_translations', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->longText('answer');
            $table->foreignId('frequently_asked_question_id')->constrained('frequently_asked_questions');
            $table->foreignId('language_id')->constrained('languages');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frequently_asked_question_translations');
    }
};
