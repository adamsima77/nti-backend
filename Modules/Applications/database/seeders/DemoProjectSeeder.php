<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Criterion;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        $organization = \Modules\Organizations\Models\Organization::query()->first();
        $publicCallType = CallType::query()->where('name', 'Verejná výzva')->first();
        $publishedStatus = StatusOfCall::query()->where('name', 'Publikované')->first();

        if (! $organization || ! $publicCallType || ! $publishedStatus) {
            return;
        }

        $mentorRole = Role::query()->where('name', 'mentor')->first();
        $studentRole = Role::query()->where('name', 'student')->first();
        $leaderTeamRole = TeamRole::query()->where('name', 'Vedúci tímu')->first();
        $memberTeamRole = TeamRole::query()->where('name', 'Člen tímu')->first();

        $programA = Program::query()->where('type_of_program_id', 1)->first();
        $programB = Program::query()->where('type_of_program_id', 2)->first();
        $criteria = Criterion::query()->pluck('id')->all();

        if (! $programA || ! $programB || ! $mentorRole || ! $studentRole || ! $leaderTeamRole || ! $memberTeamRole) {
            return;
        }

        $projectDefinitions = [
            [
                'call_name' => 'EcoTrack – Sledovanie uhlíkovej stopy',
                'program' => $programA,
                'team_name' => 'GreenTech tím',
                'description' => 'Projekt na sledovanie uhlíkovej stopy a návrhy na úsporu energie.',
                'status' => 'V hodnotení',
                'team' => [
                    ['email' => 'greentech.lead@test.nti.local', 'name' => 'Eva', 'surname' => 'Mrázová', 'role' => 'Vedúci tímu'],
                    ['email' => 'greentech.dev@test.nti.local', 'name' => 'Tomáš', 'surname' => 'Hronec', 'role' => 'Člen tímu'],
                    ['email' => 'greentech.qa@test.nti.local', 'name' => 'Lucia', 'surname' => 'Bieliková', 'role' => 'Člen tímu'],
                ],
            ],
            [
                'call_name' => 'AI chatbot pre zákaznícku podporu',
                'program' => $programB,
                'team_name' => 'AI Innovators',
                'description' => 'Chatbot pre zákaznícku podporu s integráciou AI odpovedí.',
                'status' => 'Draft',
                'team' => [
                    ['email' => 'ai.lead@test.nti.local', 'name' => 'Peter', 'surname' => 'Kováč', 'role' => 'Vedúci tímu'],
                    ['email' => 'ai.backend@test.nti.local', 'name' => 'Marek', 'surname' => 'Blaho', 'role' => 'Člen tímu'],
                    ['email' => 'ai.frontend@test.nti.local', 'name' => 'Nina', 'surname' => 'Vargová', 'role' => 'Člen tímu'],
                ],
            ],
            [
                'call_name' => 'StudyBuddy – AI asistent',
                'program' => $programA,
                'team_name' => 'EduTech',
                'description' => 'AI asistent pre štúdium a organizáciu školských úloh.',
                'status' => 'Schválené',
                'team' => [
                    ['email' => 'study.lead@test.nti.local', 'name' => 'Zuzana', 'surname' => 'Bartošová', 'role' => 'Vedúci tímu'],
                    ['email' => 'study.mobile@test.nti.local', 'name' => 'Filip', 'surname' => 'Bako', 'role' => 'Člen tímu'],
                    ['email' => 'study.ml@test.nti.local', 'name' => 'Jakub', 'surname' => 'Moravčík', 'role' => 'Člen tímu'],
                ],
            ],
        ];

        foreach ($projectDefinitions as $index => $definition) {
            $call = Call::query()->updateOrCreate(
                [
                    'program_id' => $definition['program']->id,
                    'name' => $definition['call_name'],
                ],
                [
                    'description' => $definition['description'],
                    'application_start' => now()->subDays(10 - $index),
                    'application_deadline' => now()->addWeeks(2 + $index),
                    'project_start' => now()->addMonths(1),
                    'project_end' => now()->addMonths(6),
                    'organization_id' => $organization->id,
                    'call_type_id' => $publicCallType->id,
                ]
            );

            $call->callCriteria()->syncWithoutDetaching($criteria);
            StatusOfCallHasCall::query()->updateOrCreate(
                [
                    'call_id' => $call->id,
                    'status_of_call_id' => $publishedStatus->id,
                ],
                [
                    'note' => 'Demo projektový call.',
                ]
            );

            $team = Team::query()->updateOrCreate(
                ['name' => $definition['team_name']],
                ['name' => $definition['team_name']]
            );

            foreach ($definition['team'] as $memberIndex => $memberData) {
                $user = User::query()->updateOrCreate(
                    ['email' => $memberData['email']],
                    [
                        'name' => $memberData['name'],
                        'surname' => $memberData['surname'],
                        'password' => 'Password123!',
                        'status_id' => UserStatus::ACTIVE->value,
                        'job_position' => $memberIndex === 0 ? 'Vedúci tímu (demo)' : 'Člen tímu (demo)',
                    ]
                );

                $user->forceFill(['email_verified_at' => now()])->saveQuietly();
                $user->roles()->syncWithoutDetaching([$studentRole->id]);

                $teamRoleId = $memberData['role'] === 'Vedúci tímu' ? $leaderTeamRole->id : $memberTeamRole->id;

                \Illuminate\Support\Facades\DB::table('team_members')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                    ],
                    [
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                        'team_role_id' => $teamRoleId,
                    ]
                );
            }

            $creator = User::query()->where('email', $definition['team'][0]['email'])->first();
            if (! $creator) {
                continue;
            }

            Application::query()->updateOrCreate(
                [
                    'call_id' => $call->id,
                    'team_id' => $team->id,
                ],
                [
                    'submitted_at' => now()->subDays(7 - $index),
                    'last_update' => now()->subDays(1),
                    'created_by' => $creator->id,
                    'active_status' => StatusOfApplication::query()->where('name', $definition['status'])->value('id')
                        ?? StatusOfApplication::query()->where('name', 'V hodnotení')->value('id'),
                ]
            );
        }
    }
}