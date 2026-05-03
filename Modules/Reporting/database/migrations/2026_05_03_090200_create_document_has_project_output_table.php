<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_has_project_output', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('project_output_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['document_id', 'project_output_id']);

            $table->foreign('document_id')
                ->references('id')
                ->on('document')
                ->cascadeOnDelete();

            $table->foreign('project_output_id')
                ->references('id')
                ->on('project_output')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_has_project_output');
    }
};
