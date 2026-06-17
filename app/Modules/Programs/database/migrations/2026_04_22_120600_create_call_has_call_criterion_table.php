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
        Schema::create('call_has_criterion', function (Blueprint $table) {
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('criterion_id');

            $table->primary(['call_id', 'criterion_id']);

            $table->foreign('call_id')
                ->references('id')
                ->on('call');

            $table->foreign('criterion_id')
                ->references('id')
                ->on('criterion');

            $table->integer('weight')->default(1); // Importance multiplier
            $table->boolean('is_academic_signal')->default(false); // Flags checks like grades/credits
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_has_criterion');
    }
};
