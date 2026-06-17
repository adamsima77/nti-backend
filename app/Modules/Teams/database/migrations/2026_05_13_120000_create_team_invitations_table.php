<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('team')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 128)->unique();
            $table->foreignId('team_role_id')->constrained('team_role');
            $table->foreignId('invited_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitation');
    }
};
