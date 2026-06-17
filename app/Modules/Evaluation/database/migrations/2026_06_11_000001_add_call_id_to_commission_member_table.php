<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_member', function (Blueprint $table) {
            $table->unsignedBigInteger('call_id')->nullable()->after('commission_id');

            $table->foreign('call_id')
                ->references('id')
                ->on('call')
                ->nullOnDelete();

            $table->index('call_id', 'idx_comm_member_call');
        });
    }

    public function down(): void
    {
        Schema::table('commission_member', function (Blueprint $table) {
            $table->dropForeign(['call_id']);
            $table->dropIndex('idx_comm_member_call');
            $table->dropColumn('call_id');
        });
    }
};
