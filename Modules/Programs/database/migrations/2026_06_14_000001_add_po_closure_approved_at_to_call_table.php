<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->timestamp('po_closure_approved_at')->nullable()->after('force_closed');
        });
    }

    public function down(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->dropColumn('po_closure_approved_at');
        });
    }
};
