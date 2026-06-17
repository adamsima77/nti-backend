<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_kpi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('metric_name', 255);
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->string('unit', 50)->nullable(); // e.g., '%', 'EUR', 'count', etc.
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();

            $table->foreign('application_id')
                ->references('id')
                ->on('application')
                ->cascadeOnDelete();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_kpi');
    }
};
