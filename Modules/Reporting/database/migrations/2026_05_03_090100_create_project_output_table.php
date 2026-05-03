<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_output', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('output_name', 255);
            $table->text('description')->nullable();
            $table->string('output_type', 100)->nullable(); // e.g., 'dokumentácia', 'software', 'report', etc.
            $table->string('status', 50)->default('pending'); // pending, completed, delivered
            $table->timestamp('planned_delivery')->nullable();
            $table->timestamp('actual_delivery')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();

            $table->foreign('application_id')
                ->references('id')
                ->on('application')
                ->cascadeOnDelete();

            $table->index('application_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_output');
    }
};
