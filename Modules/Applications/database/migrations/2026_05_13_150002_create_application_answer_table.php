<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_answer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('form_field_id');
            $table->longText('value');

            $table->foreign('application_id')->references('id')->on('application')->cascadeOnDelete();
            $table->foreign('form_field_id')->references('id')->on('form_field')->restrictOnDelete();
            $table->unique(['application_id', 'form_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_answer');
    }
};
