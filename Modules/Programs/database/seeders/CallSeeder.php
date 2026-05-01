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

        if ($organization === null || $publicCallType === null || $publishedStatus === null) {
            return;
        }

        $criteria = Criterion::query()->pluck('id')->all();


        $programA = Program::query()
            ->where('type_of_program_id', 1)
            ->first();

        $programB = Program::query()
            ->where('type_of_program_id', 2)
            ->first();

        /*
        | PROGRAM A CALL
        */
        if ($programA !== null) {
            $callA = Call::updateOrCreate(
                [
                    'program_id' => $programA->id,
                    'name' => 'Výzva 2026 - Program A',
                ],
                [
                    'description' => 'Podpora inovatívnych študentských tímov v programe A.',
                    'application_deadline' => now()->addMonths(1),
                    'project_start' => now()->addMonths(2),
                    'project_end' => now()->addMonths(8),
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
        }

        /*
        | PROGRAM B CALL
        */
        if ($programB !== null) {
            $callB = Call::updateOrCreate(
                [
                    'program_id' => $programB->id,
                    'name' => 'Výzva 2026 - Program B',
                ],
                [
                    'description' => 'Riesenie realnych zadani od partnerov v programe B.',
                    'application_deadline' => now()->addMonths(1),
                    'project_start' => now()->addMonths(2),
                    'project_end' => now()->addMonths(8),
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
        }
    }
}
