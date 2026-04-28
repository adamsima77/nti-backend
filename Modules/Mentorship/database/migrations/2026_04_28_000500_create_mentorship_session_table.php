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
        Schema::create('mentorship_session', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('mentorship_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('date');
            $table->text('notes');
            $table->timestamps();

            $table->index('mentorship_id', 'idx_ms_session_mentorship');
            $table->index('created_by', 'idx_ms_session_creator');

            $table->foreign('mentorship_id')
                ->references('id')
                ->on('mentorship');

            $table->foreign('created_by')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentorship_session', function (Blueprint $table) {
            $table->dropForeign(['mentorship_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('mentorship_session');
    }
};