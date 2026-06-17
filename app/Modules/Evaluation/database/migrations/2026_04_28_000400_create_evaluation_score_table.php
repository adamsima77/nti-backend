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
        Schema::create('evaluation_score', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('criterion_id');
            $table->float('score');
            $table->text('comment');
            $table->timestamps();

            $table->index('evaluation_id', 'idx_eval_score_eval');
            $table->index('criterion_id', 'idx_eval_score_crit');

            $table->foreign('evaluation_id')
                ->references('id')
                ->on('evaluation');

            $table->foreign('criterion_id')
                ->references('id')
                ->on('criterion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_score', function (Blueprint $table) {
            $table->dropForeign(['evaluation_id']);
            $table->dropForeign(['criterion_id']);
        });

        Schema::dropIfExists('evaluation_score');
    }
};