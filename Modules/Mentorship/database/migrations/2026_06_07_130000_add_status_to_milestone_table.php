<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestone', function (Blueprint $table) {
            $table->unsignedBigInteger('milestone_status_id')->nullable()->after('due_date');

            $table->foreign('milestone_status_id')
                ->references('id')
                ->on('milestone_status');
        });
    }

    public function down(): void
    {
        Schema::table('milestone', function (Blueprint $table) {
            if (Schema::hasColumn('milestone', 'milestone_status_id')) {
                $table->dropForeignIfExists('milestone_milestone_status_id_foreign');
                $table->dropColumn('milestone_status_id');
            }

            if (Schema::hasColumn('milestone', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
