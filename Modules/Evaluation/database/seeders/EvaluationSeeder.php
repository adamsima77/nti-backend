<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Decision;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\EvaluationScore;
use Modules\Programs\Models\Criterion;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $draftStatusId = StatusOfApplication::query()->where('name', 'Draft')->value('id');

        $draftApplicationIds = Application::query()
            ->where('active_status', $draftStatusId)
            ->pluck('id');

        if ($draftApplicationIds->isNotEmpty()) {
            Evaluation::query()->whereIn('application_id', $draftApplicationIds)->delete();
        }

        $applications = Application::query()
            ->where('active_status', '!=', $draftStatusId)
            ->orderBy('id')
            ->get();

        $members = CommissionMember::query()->orderBy('id')->get();
        $approvedDecision = Decision::query()->where('name', 'Schválené')->first();
        $criteria = Criterion::query()->orderBy('id')->get();

        if ($members->isEmpty() || $applications->isEmpty()) {
            $this->command?->warn('Žiadni členovia komisie alebo prihlášky. Spusti CommissionMemberSeeder a DemoProjectSeeder najskôr.');
            return;
        }

        foreach ($applications as $application) {
            $statusName = StatusOfApplication::query()->find($application->active_status)?->name;
            $isApproved = $statusName === 'Schválené';

            foreach ($members as $member) {
                $evaluation = Evaluation::query()->updateOrCreate(
                    [
                        'application_id'      => $application->id,
                        'commission_member_id' => $member->id,
                    ],
                    [
                        'decision_id'   => $isApproved ? $approvedDecision?->id : null,
                        'submitted_at'  => $isApproved ? now()->subDays(3) : null,
                        'internal_note' => $isApproved ? 'Automaticky vygenerované hodnotenie (seed).' : null,
                    ]
                );

                if ($isApproved && $criteria->isNotEmpty()) {
                    foreach ($criteria as $index => $criterion) {
                        EvaluationScore::query()->updateOrCreate(
                            [
                                'evaluation_id' => $evaluation->id,
                                'criterion_id'  => $criterion->id,
                            ],
                            [
                                'score'   => min(5, 3.5 + ($index * 0.5)),
                                'comment' => 'Seed hodnotenie.',
                            ]
                        );
                    }
                }
            }
        }
    }
}
