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
        Schema::create('commission_member', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('commission_id');

            $table->unique(['user_id', 'commission_id'], 'uq_commission_member');
            $table->index('commission_id', 'idx_comm_member_comm');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('commission_id')
                ->references('id')
                ->on('commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_member', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['commission_id']);
        });

        Schema::dropIfExists('commission_member');
    }
};