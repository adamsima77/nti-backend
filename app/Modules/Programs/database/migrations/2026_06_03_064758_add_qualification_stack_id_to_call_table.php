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
        Schema::table('call', function (Blueprint $table) {
            $table->foreignId('qualification_stack_id')->nullable()->constrained('qualification_stacks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->dropForeign('qualification_stack_id');
        });
    }
};
