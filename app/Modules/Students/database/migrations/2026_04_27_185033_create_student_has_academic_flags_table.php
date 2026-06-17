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
        Schema::create('student_has_academic_flags', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_flags_id');

            $table->primary(['student_id', 'academic_flags_id']);

            $table->foreign('student_id')
                ->references('id')
                ->on('student');

            $table->foreign('academic_flags_id')
                ->references('id')
                ->on('academic_flags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_has_academic_flags', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['academic_flags_id']);
        });

        Schema::dropIfExists('student_has_academic_flags');
    }
};
