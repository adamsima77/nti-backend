<?php

namespace Modules\Teams\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\Role;
use Modules\Students\Models\Student;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentRole = Role::where('name', 'student')->first();
        $leaderRole  = TeamRole::where('name', 'Vedúci tímu')->first();
        $memberRole  = TeamRole::where('name', 'Člen tímu')->first();

        $usersData = [
            ['email' => 'matej.kovac@example.com',  'name' => 'Matej', 'surname' => 'Kováč',  'job_position' => 'Team Leader / Scrum Master', 'team_role' => 'leader'],
            ['email' => 'tomas.vesely@example.com',  'name' => 'Tomáš', 'surname' => 'Veselý', 'job_position' => 'Backend Developer',           'team_role' => 'member'],
            ['email' => 'lucia.mala@example.com',    'name' => 'Lucia',  'surname' => 'Malá',   'job_position' => 'Frontend Developer',           'team_role' => 'member'],
        ];

        $createdUsers = [];
        foreach ($usersData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'         => $data['name'],
                    'surname'      => $data['surname'],
                    'password'     => Hash::make('secret123'),
                    'status_id'    => 1,
                    'job_position' => $data['job_position'],
                ]
            );

            if ($studentRole) {
                $user->roles()->syncWithoutDetaching([$studentRole->id]);
            }

            Student::firstOrCreate(['user_id' => $user->id]);

            $createdUsers[] = ['user' => $user, 'team_role' => $data['team_role']];
        }

        $team = Team::firstOrCreate(['name' => 'Testovací Tím']);

        foreach ($createdUsers as $entry) {
            $teamRoleId = $entry['team_role'] === 'leader' ? $leaderRole?->id : $memberRole?->id;
            $team->members()->syncWithoutDetaching([
                $entry['user']->id => ['team_role_id' => $teamRoleId],
            ]);
        }
    }
}
