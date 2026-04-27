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
        Schema::create('site_member_translations', function (Blueprint $table) {
            $table->id();
            $table->string('job_position');
            $table->foreignId('site_member_id')->constrained('site_members');
            $table->foreignId('language_id')->constrained('languages');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_member_translations');
    }
};
