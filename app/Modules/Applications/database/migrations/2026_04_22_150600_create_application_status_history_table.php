<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('status_of_application_id');
            $table->unsignedBigInteger('application_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('status_of_application_id')->references('id')->on('status_of_application');
            $table->foreign('application_id')->references('id')->on('application');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
    }
};
