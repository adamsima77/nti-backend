<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->json('application_form_schema')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->dropColumn('application_form_schema');
        });
    }
};
