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
        Schema::create('email_template_translation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('email_template_id');
            $table->unsignedBigInteger('language_id');
            $table->string('subject', 255);
            $table->longText('body_html');
            $table->timestamps();

            $table->unique(['email_template_id', 'language_id']);

            $table->foreign('email_template_id')
                ->references('id')
                ->on('email_template');

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
        Schema::table('email_template_translation', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropForeign(['language_id']);
        });

        Schema::dropIfExists('email_template_translation');
    }
};
