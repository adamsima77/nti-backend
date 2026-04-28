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
        Schema::create('milestone_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('milestone_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_comment_id')->nullable();
            $table->text('comment_text');
            $table->timestamps();

            $table->index('milestone_id', 'idx_ms_comment_ms');
            $table->index('user_id', 'idx_ms_comment_user');
            $table->index('parent_comment_id', 'idx_ms_comment_parent');

            $table->foreign('milestone_id')
                ->references('id')
                ->on('milestone');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('parent_comment_id')
                ->references('id')
                ->on('milestone_comments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_comments', function (Blueprint $table) {
            $table->dropForeign(['milestone_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['parent_comment_id']);
        });

        Schema::dropIfExists('milestone_comments');
    }
};