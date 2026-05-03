<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organizations\Models\Organization;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Criterion;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;

class CallSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->first();
        $publicCallType = CallType::query()->where('name', 'Verejná výzva')->first();
        $publishedStatus = StatusOfCall::query()->where('name', 'Publikované')->first();

        if (!$organization || !$publicCallType || !$publishedStatus) {
            return;
        }

        $criteria = Criterion::query()->pluck('id')->all();

        $programs = Program::query()
            ->whereIn('type_of_program_id', [1, 2])
            ->get()
            ->keyBy('type_of_program_id');


        if (isset($programs[1])) {

            $applicationStart = now()->subDays(3);
            $applicationDeadline = now()->addMonth();
            $projectStart = now()->addMonths(2);
            $projectEnd = now()->addMonths(8);

            $callA = Call::updateOrCreate(
                [
                    'program_id' => $programs[1]->id,
                    'name' => 'Výzva 2026 - Program A',
                ],
                [
                    'description' => 'Podpora inovatívnych študentských tímov v programe A.',

                    'application_start' => $applicationStart,
                    'application_deadline' => $applicationDeadline,
                    'project_start' => $projectStart,
                    'project_end' => $projectEnd,

                    'organization_id' => $organization->id,
                    'call_type_id' => $publicCallType->id,
                ]
            );


            $callA->callCriteria()->syncWithoutDetaching($criteria);


            StatusOfCallHasCall::updateOrCreate(
                [
                    'call_id' => $callA->id,
                    'status_of_call_id' => $publishedStatus->id,
                ],
                [
                    'note' => 'Inicialny publikovany stav.',
                ]
            );


            $callA->callTranslations()->updateOrCreate(
                ['language_id' => 1],
                [
                    'name' => 'Výzva 2026 - Program A',
                    'description' => 'Podpora inovatívnych študentských tímov v programe A.',
                ]
            );

            $callA->callTranslations()->updateOrCreate(
                ['language_id' => 2], // EN
                [
                    'name' => 'Call 2026 - Program A',
                    'description' => 'Support for innovative student teams in Program A.',
                ]
            );
        }


        if (isset($programs[2])) {

            $applicationStart = now()->addDays(5);
            $applicationDeadline = now()->addMonths(1);
            $projectStart = now()->addMonths(2);
            $projectEnd = now()->addMonths(8);

            $callB = Call::updateOrCreate(
                [
                    'program_id' => $programs[2]->id,
                    'name' => 'Výzva 2026 - Program B',
                ],
                [
                    'description' => 'Riesenie realnych zadani od partnerov v programe B.',

                    'application_start' => $applicationStart,
                    'application_deadline' => $applicationDeadline,
                    'project_start' => $projectStart,
                    'project_end' => $projectEnd,

                    'organization_id' => $organization->id,
                    'call_type_id' => $publicCallType->id,
                ]
            );


            $callB->callCriteria()->syncWithoutDetaching($criteria);


            StatusOfCallHasCall::updateOrCreate(
                [
                    'call_id' => $callB->id,
                    'status_of_call_id' => $publishedStatus->id,
                ],
                [
                    'note' => 'Inicialny publikovany stav.',
                ]
            );


            $callB->callTranslations()->updateOrCreate(
                ['language_id' => 1],
                [
                    'name' => 'Výzva 2026 - Program B',
                    'description' => 'Riesenie realnych zadani od partnerov v programe B.',
                ]
            );

            $callB->callTranslations()->updateOrCreate(
                ['language_id' => 2], // EN
                [
                    'name' => 'Call 2026 - Program B',
                    'description' => 'Solving real-world challenges from partners in Program B.',
                ]
            );
        }
    }
}
