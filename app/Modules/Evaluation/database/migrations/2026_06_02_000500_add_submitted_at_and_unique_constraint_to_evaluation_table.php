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
        Schema::table('evaluation', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('decision_id');
            $table->unique(['application_id', 'commission_member_id'], 'uniq_eval_application_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation', function (Blueprint $table) {
            $table->dropUnique('uniq_eval_application_member');
            $table->dropColumn('submitted_at');
        });
    }
};
