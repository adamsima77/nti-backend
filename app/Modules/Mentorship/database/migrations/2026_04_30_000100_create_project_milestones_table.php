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
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->foreignId('milestone_status_id')->default(1)
                ->constrained('milestone_status');
            $table->text('comments')->nullable();
            $table->foreignId('call_id')->constrained('call');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropForeign(['call_id']);
            $table->dropForeign(['milestone_status_id']);
        });

        Schema::dropIfExists('project_milestones');
    }
};
