<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->dropColumn('max_teams');
        });
    }

    public function down(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->integer('max_teams')->default(1)->after('tech_tags');
        });
    }
};
