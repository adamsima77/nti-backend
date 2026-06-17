<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('application_field', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('validation_rules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_field');
    }
};
