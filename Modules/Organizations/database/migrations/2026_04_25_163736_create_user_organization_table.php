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
        Schema::create('user_organization', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('organization_role');

            $table->primary(['user_id', 'organization_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('user');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organization');

            $table->foreign('organization_role')
                ->references('id')
                ->on('organization_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_organization', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['organization_role']);
        });
        
        Schema::dropIfExists('user_organization');
    }
};
