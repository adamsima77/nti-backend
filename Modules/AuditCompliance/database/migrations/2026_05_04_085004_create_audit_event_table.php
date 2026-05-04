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
        Schema::create('audit_event', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('action', 255);
            $table->string('object_type', 255);
            $table->unsignedBigInteger('object_id');
            $table->string('ip', 120);
            $table->string('result', 120);
            $table->json('result_payload')->nullable();
            $table->timestamp('time_of_action')->useCurrent();

            $table->index('user_id', 'idx_audit_user');
            $table->index(['object_type', 'object_id'], 'idx_audit_object');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_event', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('audit_event');
    }
};
