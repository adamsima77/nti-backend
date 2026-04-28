<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\IdentityAccess\Models\User;

class CommissionMemberSeeder extends Seeder
{
    public function run(): void
    {
        $commission = Commission::query()->first();

        if ($commission === null) {
            return;
        }

        $users = User::query()->orderBy('id')->take(3)->get();

        foreach ($users as $user) {
            CommissionMember::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'commission_id' => $commission->id,
                ],
                [
                    'user_id' => $user->id,
                    'commission_id' => $commission->id,
                ]
            );
        }
    }
}