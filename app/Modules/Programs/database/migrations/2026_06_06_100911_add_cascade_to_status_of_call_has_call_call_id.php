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
        Schema::table('status_of_call_has_call', function (Blueprint $table) {
            $table->dropForeign(['call_id']);
            $table->foreign('call_id')->references('id')->on('call')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_of_call_has_call', function (Blueprint $table) {
            $table->dropForeign(['call_id']);
            $table->foreign('call_id')->references('id')->on('call');
        });
    }
};
