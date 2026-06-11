<?php

namespace Modules\Teams\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\Role;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;
use Modules\Students\Models\Student;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Načítanie globálnej roly pre študentov a tímových rolí
        $studentRole = Role::where('name', 'student')->first();
        $leaderRole  = TeamRole::where('name', 'Vedúci tímu')->first();
        $memberRole  = TeamRole::where('name', 'Člen tímu')->first();

        // 2. Vytvorenie 1. používateľa (Team Leader)
        $leaderUser = User::create([
            'name'         => 'Matej',
            'surname'      => 'Kováč',
            'email'        => 'matej.kovac@example.com',
            'password'     => Hash::make('secret123'),
            'status_id'    => 1, // Aktívny status
            'job_position' => 'Team Leader / Scrum Master',
        ]);

        if ($studentRole) {
            $leaderUser->roles()->attach($studentRole->id);
        }
        Student::create(['user_id' => $leaderUser->id]);

        // 3. Vytvorenie 2. používateľa (Člen 1)
        $memberUser1 = User::create([
            'name'         => 'Tomáš',
            'surname'      => 'Veselý',
            'email'        => 'tomas.vesely@example.com',
            'password'     => Hash::make('secret123'),
            'status_id'    => 1,
            'job_position' => 'Backend Developer',
        ]);

        if ($studentRole) {
            $memberUser1->roles()->attach($studentRole->id);
        }
        Student::create(['user_id' => $memberUser1->id]);

        // 4. Vytvorenie 3. používateľa (Člen 2)
        $memberUser2 = User::create([
            'name'         => 'Lucia',
            'surname'      => 'Malá',
            'email'        => 'lucia.mala@example.com',
            'password'     => Hash::make('secret123'),
            'status_id'    => 1,
            'job_position' => 'Frontend Developer',
        ]);

        if ($studentRole) {
            $memberUser2->roles()->attach($studentRole->id);
        }
        Student::create(['user_id' => $memberUser2->id]);

        // 5. Vytvorenie nového tímu
        $team = Team::create([
            'name' => 'Testovací Tím',
        ]);

        // 6. Prepojenie všetkých troch novovytvorených používateľov do tímu
        $team->members()->attach([
            $leaderUser->id  => ['team_role_id' => $leaderRole?->id],
            $memberUser1->id => ['team_role_id' => $memberRole?->id],
            $memberUser2->id => ['team_role_id' => $memberRole?->id],
        ]);
    }
}
