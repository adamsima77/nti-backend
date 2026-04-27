<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('program') || !Schema::hasTable('call')) {
            return;
        }

        $this->renameProgramTypeColumn();
        $this->upgradeCriterionTables();
        $this->upgradeCallTable();
        $this->ensureCallStatusHistoryTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty. This migration performs data-preserving schema upgrades.
    }

    private function renameProgramTypeColumn(): void
    {
        if (Schema::hasColumn('program', 'type_of_program') && !Schema::hasColumn('program', 'type_of_program_id')) {
            $this->dropForeignKeyByColumn('program', 'type_of_program');
            DB::statement('ALTER TABLE program CHANGE type_of_program type_of_program_id BIGINT UNSIGNED NOT NULL');

            Schema::table('program', function (Blueprint $table) {
                $table->foreign('type_of_program_id')->references('id')->on('type_of_program');
            });
        }
    }

    private function upgradeCriterionTables(): void
    {
        if (Schema::hasTable('call_criterion') && !Schema::hasTable('criterion')) {
            Schema::rename('call_criterion', 'criterion');
        }

        if (Schema::hasTable('call_has_call_criterion') && !Schema::hasTable('call_has_criterion')) {
            Schema::rename('call_has_call_criterion', 'call_has_criterion');
        }

        if (Schema::hasTable('call_has_criterion') && Schema::hasColumn('call_has_criterion', 'call_criterion_id') && !Schema::hasColumn('call_has_criterion', 'criterion_id')) {
            $this->dropForeignKeyByColumn('call_has_criterion', 'call_criterion_id');
            DB::statement('ALTER TABLE call_has_criterion CHANGE call_criterion_id criterion_id BIGINT UNSIGNED NOT NULL');
            $this->setCallCriterionPivotPrimaryKey();

            Schema::table('call_has_criterion', function (Blueprint $table) {
                $table->foreign('criterion_id')->references('id')->on('criterion');
            });
        }
    }

    private function upgradeCallTable(): void
    {
        if (Schema::hasColumn('call', 'organization') && !Schema::hasColumn('call', 'organization_id')) {
            $this->dropForeignKeyByColumn('call', 'organization');
            DB::statement('ALTER TABLE call CHANGE organization organization_id BIGINT UNSIGNED NOT NULL');

            Schema::table('call', function (Blueprint $table) {
                $table->foreign('organization_id')->references('id')->on('organization');
            });
        }

        if (Schema::hasColumn('call', 'call_type') && !Schema::hasColumn('call', 'call_type_id')) {
            $this->dropForeignKeyByColumn('call', 'call_type');
            DB::statement('ALTER TABLE call CHANGE call_type call_type_id BIGINT UNSIGNED NOT NULL');

            Schema::table('call', function (Blueprint $table) {
                $table->foreign('call_type_id')->references('id')->on('call_type');
            });
        }
    }

    private function ensureCallStatusHistoryTable(): void
    {
        if (!Schema::hasTable('status_of_call_has_call')) {
            Schema::create('status_of_call_has_call', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('call_id');
                $table->unsignedBigInteger('status_of_call_id');
                $table->text('note')->nullable();
                $table->timestamps();

                $table->foreign('call_id')->references('id')->on('call');
                $table->foreign('status_of_call_id')->references('id')->on('status_of_call');
            });
        }

        if (Schema::hasColumn('call', 'active_status')) {
            DB::statement(
                'INSERT INTO status_of_call_has_call (call_id, status_of_call_id, note, created_at, updated_at) '
                . 'SELECT c.id, c.active_status, "Migrated from active_status", NOW(), NOW() '
                . 'FROM call c '
                . 'WHERE c.active_status IS NOT NULL '
                . 'AND NOT EXISTS ('
                . 'SELECT 1 FROM status_of_call_has_call s '
                . 'WHERE s.call_id = c.id AND s.status_of_call_id = c.active_status'
                . ')'
            );

            $this->dropForeignKeyByColumn('call', 'active_status');
            Schema::table('call', function (Blueprint $table) {
                $table->dropColumn('active_status');
            });
        }
    }

    private function setCallCriterionPivotPrimaryKey(): void
    {
        $primaryName = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'call_has_criterion')
            ->where('CONSTRAINT_TYPE', 'PRIMARY KEY')
            ->value('CONSTRAINT_NAME');

        if ($primaryName !== null) {
            DB::statement('ALTER TABLE call_has_criterion DROP PRIMARY KEY');
        }

        DB::statement('ALTER TABLE call_has_criterion ADD PRIMARY KEY (call_id, criterion_id)');
    }

    private function dropForeignKeyByColumn(string $table, string $column): void
    {
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint !== null) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
        }
    }
};
