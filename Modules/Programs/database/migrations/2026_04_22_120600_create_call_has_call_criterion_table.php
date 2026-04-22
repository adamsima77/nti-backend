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
        Schema::create('call_has_call_criterion', function (Blueprint $table) {
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('call_criterion_id');

            $table->primary(['call_id', 'call_criterion_id']);

            $table->foreign('call_id')
                ->references('id')
                ->on('call');

            $table->foreign('call_criterion_id')
                ->references('id')
                ->on('call_criterion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_has_call_criterion');
    }
};
