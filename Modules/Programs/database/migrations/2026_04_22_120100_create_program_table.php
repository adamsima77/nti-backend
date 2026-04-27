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
        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('type_of_program_id');
            $table->text('description');
            $table->timestamps();

            $table->foreign('type_of_program_id')
                ->references('id')
                ->on('type_of_program');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program');
    }
};
