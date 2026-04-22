<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('application', function (Blueprint $table) {
            $table->id();
            $table->timestamp('submitted_at');
            $table->timestamp('last_update');
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('active_status');

            $table->foreign('call_id')->references('id')->on('call');
            $table->foreign('team_id')->references('id')->on('team');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('active_status')->references('id')->on('status_of_application');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application');
    }
};
