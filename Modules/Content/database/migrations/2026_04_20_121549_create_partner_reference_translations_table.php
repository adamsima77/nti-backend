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
        Schema::create('partner_reference_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('job_position', 255);
            $table->text('description');
            $table->foreignId('partner_reference_id')->constrained()->onDelete('cascade');
            $table->foreignId('language_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_reference_translations');
    }
};
