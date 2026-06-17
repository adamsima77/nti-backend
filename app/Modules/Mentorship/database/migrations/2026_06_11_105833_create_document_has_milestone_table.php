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
            $table->foreignId('document_id')->constrained('document');
            $table->foreignId('milestone_id')->constrained('project_milestones');
            $table->primary(['document_id', 'milestone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_has_milestone', function (Blueprint $table) {
            Schema::dropIfExists('document_has_milestone');
        });

        Schema::dropIfExists('document_has_milestone');
    }
};
