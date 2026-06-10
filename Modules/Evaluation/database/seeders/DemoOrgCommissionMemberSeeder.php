<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\Decision;
use Modules\IdentityAccess\Database\Seeders\DemoOrganizationUserSeeder;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Applications\Models\Application;

class DemoOrgCommissionMemberSeeder extends Seeder
{
    public function run(): void
    {
        $commission = Commission::query()->first();

        if ($commission === null) {
            $this->command?->error('No commission found. Run CommissionSeeder first.');
            return;
        }

        $user = User::query()->where('email', DemoOrganizationUserSeeder::EMAIL)->first();

        if ($user === null) {
            $this->command?->error('Demo organization user not found. Run DemoOrganizationUserSeeder first.');
            return;
        }

        $call = Call::query()
            ->whereHas('applications', fn ($q) => $q->whereNotNull('submitted_at'))
            ->latest('id')
            ->first();

        if ($call === null) {
            $this->command?->error('No call found. Seed program calls first.');
            return;
        }

        $orgMember = CommissionMember::query()->updateOrCreate(
            [
                'user_id'       => $user->id,
                'commission_id' => $commission->id,
            ],
            []
        );

        $decision = Decision::query()->orderBy('id')->first();

        $applicationIds = Application::query()
            ->where('call_id', $call->id)
            ->whereNotNull('submitted_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($applicationIds->take(3) as $applicationId) {
            Evaluation::query()->updateOrCreate(
                [
                    'application_id'       => $applicationId,
                    'commission_member_id' => $orgMember->id,
                ],
                [
                    'decision_id'   => null,
                    'submitted_at'  => null,
                    'internal_note' => null,
                ]
            );
        }

        $this->command?->newLine();
        $this->command?->info('Demo org commission member seeded:');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email',       $user->email],
                ['Name',        "{$user->name} {$user->surname}"],
                ['Commission',  "#{$commission->id} {$commission->name}"],
                ['Call',        "#{$call->id} {$call->name}"],
                ['Evaluations', $applicationIds->take(3)->count()],
            ]
        );
    }
}
