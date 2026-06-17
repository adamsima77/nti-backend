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
        Schema::create('notification_translation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('language_id');
            $table->string('title', 255);
            $table->text('body');
            $table->timestamps();

            $table->unique(['notification_id', 'language_id']);

            $table->foreign('notification_id')
                ->references('id')
                ->on('notification');

            $table->foreign('language_id')
                ->references('id')
                ->on('languages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_translation', function (Blueprint $table) {
            $table->dropForeign(['notification_id']);
            $table->dropForeign(['language_id']);
        });

        Schema::dropIfExists('notification_translation');
    }
};
