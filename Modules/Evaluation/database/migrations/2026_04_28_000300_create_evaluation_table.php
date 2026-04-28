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
        Schema::create('evaluation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('commission_member_id');
            $table->unsignedBigInteger('decision_id');
            $table->timestamps();

            $table->index('application_id', 'idx_eval_app');
            $table->index('commission_member_id', 'idx_eval_member');
            $table->index('decision_id', 'idx_eval_decision');

            $table->foreign('application_id')
                ->references('id')
                ->on('application');

            $table->foreign('commission_member_id')
                ->references('id')
                ->on('commission_member');

            $table->foreign('decision_id')
                ->references('id')
                ->on('decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropForeign(['commission_member_id']);
            $table->dropForeign(['decision_id']);
        });

        Schema::dropIfExists('evaluation');
    }
};