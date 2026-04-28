<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mentorship', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mentor_user_id');
            $table->unsignedBigInteger('application_id');
            $table->timestamps();

            $table->unique(['mentor_user_id', 'application_id'], 'uq_mentorship');
            $table->index('application_id', 'idx_mentorship_app');

            $table->foreign('mentor_user_id')
                ->references('id')
                ->on('users');

            $table->foreign('application_id')
                ->references('id')
                ->on('application');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentorship', function (Blueprint $table) {
            $table->dropForeign(['mentor_user_id']);
            $table->dropForeign(['application_id']);
        });

        Schema::dropIfExists('mentorship');
    }
};