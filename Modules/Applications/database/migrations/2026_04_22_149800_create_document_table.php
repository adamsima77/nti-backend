<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users');
            $table->foreignId('security_classification_id')->constrained('security_classification');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document');
    }
};
