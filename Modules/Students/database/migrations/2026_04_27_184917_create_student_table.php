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
        Schema::create('student', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('study_program_id');
            $table->unsignedInteger('study_field_id');
            $table->unsignedInteger('university_id');
            $table->unsignedBigInteger('cv_document_id')->nullable();
            $table->integer('year_of_study');
            $table->string('portfolio_url', 255)->nullable();
            $table->timestamps();

            $table->unique('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('study_program_id')
                ->references('id')
                ->on('study_program');

            $table->foreign('study_field_id')
                ->references('id')
                ->on('study_field');

            $table->foreign('university_id')
                ->references('id')
                ->on('university');

            $table->foreign('cv_document_id')
                ->references('id')
                ->on('document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['study_program_id']);
            $table->dropForeign(['study_field_id']);
            $table->dropForeign(['university_id']);
            $table->dropForeign(['cv_document_id']);
        });

        Schema::dropIfExists('student');
    }
};
