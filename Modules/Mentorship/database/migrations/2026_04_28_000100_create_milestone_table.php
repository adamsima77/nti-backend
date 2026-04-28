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
        Schema::create('milestone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('call_id');
            $table->string('name', 255);
            $table->text('description');
            $table->dateTime('due_date');
            $table->timestamps();

            $table->index('call_id', 'idx_milestone_call');

            $table->foreign('call_id')
                ->references('id')
                ->on('call');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone', function (Blueprint $table) {
            $table->dropForeign(['call_id']);
        });

        Schema::dropIfExists('milestone');
    }
};