<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('application_field_value', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('application_field_id');
            $table->text('value');

            $table->foreign('application_id')->references('id')->on('application');
            $table->foreign('application_field_id')->references('id')->on('application_field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_field_value');
    }
};
