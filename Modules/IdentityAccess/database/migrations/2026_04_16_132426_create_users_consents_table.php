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
        Schema::create('users_consents', function (Blueprint $table) {
            $table->id();
            $table->boolean('granted')->default(false);
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip', 255);
            $table->string('user_agent', 255);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('consent_id')->constrained('consent_types')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_consents');
    }
};
