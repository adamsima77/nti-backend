<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\EvaluationScore;
use Modules\Programs\Models\Criterion;

class EvaluationScoreSeeder extends Seeder
{
    public function run(): void
    {
        $evaluation = Evaluation::query()
            ->orderBy('id')
            ->first();

        if ($evaluation === null) {
            return;
        }

        $criteria = Criterion::query()
            ->orderBy('id')
            ->get();

        if ($criteria->isEmpty()) {
            return;
        }

        foreach ($criteria as $index => $criterion) {
            EvaluationScore::query()->updateOrCreate(
                [
                    'evaluation_id' => $evaluation->id,
                    'criterion_id' => $criterion->id,
                ],
                [
                    'evaluation_id' => $evaluation->id,
                    'criterion_id' => $criterion->id,
                    'score' => 3.5 + ($index * 0.5),
                    'comment' => 'Automaticky seeded score pre testovacie dáta.',
                ]
            );
        }
    }
}