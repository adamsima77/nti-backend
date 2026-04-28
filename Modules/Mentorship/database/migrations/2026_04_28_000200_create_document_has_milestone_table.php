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
        Schema::create('document_has_milestone', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('milestone_id');

            $table->primary(['document_id', 'milestone_id']);
            $table->index('milestone_id', 'idx_doc_ms_ms');

            $table->foreign('document_id')
                ->references('id')
                ->on('document');

            $table->foreign('milestone_id')
                ->references('id')
                ->on('milestone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_has_milestone', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['milestone_id']);
        });

        Schema::dropIfExists('document_has_milestone');
    }
};