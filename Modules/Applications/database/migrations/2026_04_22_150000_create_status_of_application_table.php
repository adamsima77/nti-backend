<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('status_of_application', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_of_application');
    }
};
