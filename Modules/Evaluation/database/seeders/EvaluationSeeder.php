<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\Application;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Decision;
use Modules\Evaluation\Models\Evaluation;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $applicationIds = Application::query()
            ->orderBy('id')
            ->pluck('id');

        $commissionMember = CommissionMember::query()
            ->orderBy('id')
            ->first();

        $decision = Decision::query()
            ->orderBy('id')
            ->first();

        if ($commissionMember === null || $decision === null || $applicationIds->isEmpty()) {
            return;
        }

        foreach ($applicationIds->take(3) as $applicationId) {
            Evaluation::query()->updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'commission_member_id' => $commissionMember->id,
                ],
                [
                    'application_id' => $applicationId,
                    'commission_member_id' => $commissionMember->id,
                    'decision_id' => $decision->id,
                ]
            );
        }
    }
}