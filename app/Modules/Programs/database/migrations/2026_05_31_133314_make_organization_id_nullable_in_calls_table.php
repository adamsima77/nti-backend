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
            $table->unsignedBigInteger('organization_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
        });
    }
};
