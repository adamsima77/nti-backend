<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoMentorUserSeeder extends Seeder
{
    public const EMAIL = 'mentor@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'mentor')->first();

        if (! $role) {
            $this->command?->error('Role `mentor` does not exist.');
            return;
        }

        $mentors = [
            [
                'email' => self::EMAIL,
                'name' => 'Matej',
                'surname' => 'Novotný',
                'job_position' => 'Mentor (demo)',
            ],
            [
                'email' => 'ivana.kovacova@test.nti.local',
                'name' => 'Ivana',
                'surname' => 'Kováčová',
                'job_position' => 'Senior Software Mentor',
            ],
            [
                'email' => 'peter.nemec@test.nti.local',
                'name' => 'Peter',
                'surname' => 'Nemec',
                'job_position' => 'Product Mentor',
            ],
            [
                'email' => 'veronika.horvathova@test.nti.local',
                'name' => 'Veronika',
                'surname' => 'Horváthová',
                'job_position' => 'Business Strategy Mentor',
            ],
            [
                'email' => 'michal.sklenar@test.nti.local',
                'name' => 'Michal',
                'surname' => 'Sklenár',
                'job_position' => 'Growth Mentor',
            ],
        ];

        foreach ($mentors as $mentorData) {
            $mentor = User::query()->updateOrCreate(
                ['email' => $mentorData['email']],
                [
                    'name'         => $mentorData['name'],
                    'surname'      => $mentorData['surname'],
                    'password'     => self::PASSWORD, // hashed via User cast
                    'status_id'    => UserStatus::ACTIVE->value,
                    'job_position' => $mentorData['job_position'],
                    'avatar'       => 'https://ui-avatars.com/api/?name=' . urlencode($mentorData['name'] . ' ' . $mentorData['surname']) . '&background=edf2f7&color=3b82f6&size=256&rounded=true',
                ]
            );

            $mentor->forceFill(['email_verified_at' => now()])->saveQuietly();
            $mentor->roles()->sync([$role->id]);
        }

        $this->command?->newLine();
        $this->command?->info('✅ Demo mentors created/updated: ' . count($mentors));
    }
}

