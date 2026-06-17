<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_of_call_has_call', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('status_of_call_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('call');
            $table->foreign('status_of_call_id')->references('id')->on('status_of_call');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_of_call_has_call');
    }
};
