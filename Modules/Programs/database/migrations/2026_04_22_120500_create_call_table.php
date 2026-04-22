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
        Schema::create('call', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('application_deadline');
            $table->timestamp('project_start');
            $table->timestamp('project_end');
            $table->text('description');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('organization');
            $table->unsignedBigInteger('call_type');
            $table->unsignedBigInteger('active_status');
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('program');
            $table->foreign('organization')->references('id')->on('organization');
            $table->foreign('call_type')->references('id')->on('call_type');
            $table->foreign('active_status')->references('id')->on('status_of_call');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call');
    }
};
