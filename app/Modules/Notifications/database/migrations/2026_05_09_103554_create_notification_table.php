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
        Schema::create('notification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('notification_category_id');
            $table->string('notifiable_type', 255)->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('title', 255);
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_notification_user');
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notification_morphs');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('notification_category_id')
                ->references('id')
                ->on('notification_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['notification_category_id']);
        });

        Schema::dropIfExists('notification');
    }
};
