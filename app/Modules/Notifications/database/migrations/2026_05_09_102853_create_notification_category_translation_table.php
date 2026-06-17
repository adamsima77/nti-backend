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
        Schema::create('notification_category_translation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('notification_category_id');
            $table->unsignedBigInteger('language_id');
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['notification_category_id', 'language_id']);

            $table->foreign('notification_category_id')
                ->references('id')
                ->on('notification_category');

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
        Schema::table('notification_category_translation', function (Blueprint $table) {
            $table->dropForeign(['notification_category_id']);
            $table->dropForeign(['language_id']);
        });

        Schema::dropIfExists('notification_category_translation');
    }
};
