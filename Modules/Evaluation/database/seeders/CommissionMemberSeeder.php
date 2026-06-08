<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\IdentityAccess\Database\Seeders\DemoEvaluatorUserSeeder;
use Modules\IdentityAccess\Models\User;

class CommissionMemberSeeder extends Seeder
{
    public function run(): void
    {
        $commission = Commission::query()->first();

        if ($commission === null) {
            $this->command?->error('No commission found. Run CommissionSeeder first.');
            return;
        }

        $emails = [
            DemoEvaluatorUserSeeder::EMAIL_1,
            DemoEvaluatorUserSeeder::EMAIL_2,
        ];

        $users = User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        $missing = array_diff($emails, $users->keys()->toArray());

        if (! empty($missing)) {
            $this->command?->warn('Missing demo users: ' . implode(', ', $missing));
            $this->command?->warn('Run DemoCommissionChairUserSeeder and DemoEvaluatorUserSeeder first.');
        }

        foreach ($users as $user) {
            CommissionMember::query()->updateOrCreate(
                [
                    'user_id'       => $user->id,
                    'commission_id' => $commission->id,
                ],
                [
                    'user_id'       => $user->id,
                    'commission_id' => $commission->id,
                ]
            );
        }

        $this->command?->newLine();
        $this->command?->info("Seeded {$users->count()} member(s) into commission #{$commission->id}:");
        $this->command?->table(
            ['Email', 'Name'],
            $users->map(fn ($u) => [$u->email, "{$u->name} {$u->surname}"])->values()->toArray()
        );
    }
}
