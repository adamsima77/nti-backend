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
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('call_type_id');
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('program');
            $table->foreign('organization_id')->references('id')->on('organization');
            $table->foreign('call_type_id')->references('id')->on('call_type');
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
