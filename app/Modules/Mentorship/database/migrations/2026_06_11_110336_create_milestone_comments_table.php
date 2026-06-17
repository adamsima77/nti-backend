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
            $table->foreignId('milestone_id')->constrained('project_milestones');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('parent_comment_id')->nullable()->constrained('milestone_comments');
            $table->text('comment_text');
            $table->timestamps();
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
