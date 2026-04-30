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
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->date('deadline');
            $table->string('status', 120);
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('project_id');
            $table->timestamps();

            $table->index('project_id', 'idx_project_milestones_project');

            $table->foreign('project_id')
                ->references('id')
                ->on('application');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::dropIfExists('project_milestones');
    }
};
