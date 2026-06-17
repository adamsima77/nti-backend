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
        Schema::create('sector_has_organization', function (Blueprint $table) {
            $table->unsignedBigInteger('sector_id');
            $table->unsignedBigInteger('organization_id');

            $table->primary(['sector_id', 'organization_id']);

            $table->foreign('sector_id')
                ->references('id')
                ->on('sector');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sector_has_organization', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
            $table->dropForeign(['organization_id']);
        });

        Schema::dropIfExists('sector_has_organization');
    }
};
