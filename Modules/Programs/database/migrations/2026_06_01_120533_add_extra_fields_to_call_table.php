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
            $table->decimal('budget', 12, 2)->nullable()->after('description');
            $table->text('tech_spec')->nullable()->after('budget');
            $table->json('tech_tags')->nullable()->after('tech_spec');
            $table->integer('max_teams')->default(1)->after('tech_tags');
            $table->string('budget_type')->default('milestone')->after('max_teams');
            $table->unsignedBigInteger('po_user_id')->nullable()->after('budget_type');
            $table->foreign('po_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call', function (Blueprint $table) {
            $table->dropForeign(['po_user_id']);
            $table->dropColumn([
                'budget',
                'tech_spec',
                'tech_tags',
                'max_teams',
                'budget_type',
                'po_user_id',
            ]);
        });
    }
};
