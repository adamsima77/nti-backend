<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application', function (Blueprint $table) {
            $table->unsignedBigInteger('form_schema_id')->nullable()->after('call_id');
            $table->foreign('form_schema_id')->references('id')->on('form_schema')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application', function (Blueprint $table) {
            $table->dropForeign(['form_schema_id']);
            $table->dropColumn('form_schema_id');
        });
    }
};
