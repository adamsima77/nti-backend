<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_commission_setup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->unique()->constrained('call')->cascadeOnDelete();
            $table->foreignId('commission_id')->constrained('commission')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_commission_setup');
    }
};
