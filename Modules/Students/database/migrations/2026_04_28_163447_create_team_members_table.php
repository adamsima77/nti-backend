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
        Schema::create('team_members', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('team_role_id');

            $table->primary(['user_id', 'team_id']);

            $table->index('team_id', 'idx_team_members_team');
            $table->index('team_role_id', 'idx_team_members_role');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('team_id')
                ->references('id')
                ->on('team');

            $table->foreign('team_role_id')
                ->references('id')
                ->on('team_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['team_id']);
            $table->dropForeign(['team_role_id']);
        });

        Schema::dropIfExists('team_members');
    }
};
