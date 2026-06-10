<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Database\Seeders\DemoMentorUserSeeder;
use Modules\IdentityAccess\Models\User;

class MentorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $mentor = User::query()->where('email', DemoMentorUserSeeder::EMAIL)->first();
        $applications = Application::query()
            ->with(['creator:id,name,surname,email'])
            ->orderBy('id')
            ->take(3)
            ->get();

        if (! $mentor || $applications->isEmpty()) {
            return;
        }

        $milestoneTemplates = [
            [
                'name' => 'Analýza a návrh architektúry',
                'description' => 'Dokumentácia technickej architektúry, ERD diagram, API kontrakt.',
                'deadline' => now()->addDays(10),
                'status' => 'completed',
                'comments' => [
                    ['author' => 'mentor', 'text' => 'Skvelá práca, architektúra je čistá. Odporúčam pridať rate limiting do API kontraktu.'],
                    ['author' => 'creator', 'text' => 'Zapracované, ďakujeme za feedback.'],
                ],
            ],
            [
                'name' => 'MVP — funkčný prototyp',
                'description' => 'Základná funkcionalita s najdôležitejšími flowmi.',
                'deadline' => now()->addWeeks(3),
                'status' => 'pending_approval',
                'comments' => [
                    ['author' => 'creator', 'text' => 'MVP je hotový, čakáme na schválenie mentora.'],
                ],
            ],
            [
                'name' => 'Integrácia a finalizácia',
                'description' => 'Napojenie na externé služby a príprava odovzdania.',
                'deadline' => now()->addWeeks(6),
                'status' => 'in_progress',
                'comments' => [],
            ],
        ];

        foreach ($applications as $applicationIndex => $application) {
            DB::table('mentorship')->updateOrInsert(
                [
                    'mentor_user_id' => $mentor->id,
                    'application_id' => $application->id,
                ],
                [
                    'mentor_user_id' => $mentor->id,
                    'application_id' => $application->id,
                    'created_at' => now()->subDays(12 - $applicationIndex),
                    'updated_at' => now(),
                ]
            );

            $milestoneIds = [];

            foreach ($milestoneTemplates as $milestoneIndex => $template) {
                $legacyMilestoneId = DB::table('milestone')->updateOrInsert(
                    [
                        'call_id' => $application->call_id,
                        'name' => $template['name'],
                    ],
                    [
                        'call_id' => $application->call_id,
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'due_date' => $template['deadline'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $projectMilestoneId = DB::table('project_milestones')->updateOrInsert(
                    [
                        'project_id' => $application->id,
                        'name' => $template['name'],
                    ],
                    [
                        'project_id' => $application->id,
                        'name' => $template['name'],
                        'deadline' => $template['deadline']->toDateString(),
                        'status' => $template['status'],
                        'comments' => $template['description'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $milestoneId = DB::table('project_milestones')
                    ->where('project_id', $application->id)
                    ->where('name', $template['name'])
                    ->value('id');

                $legacyMilestoneId = DB::table('milestone')
                    ->where('call_id', $application->call_id)
                    ->where('name', $template['name'])
                    ->value('id');

                if ($legacyMilestoneId !== null) {
                    foreach ($template['comments'] as $commentIndex => $commentTemplate) {
                        $author = $commentTemplate['author'] === 'mentor'
                            ? $mentor
                            : ($application->creator ?? $mentor);

                        DB::table('milestone_comments')->updateOrInsert(
                            [
                                'milestone_id' => $legacyMilestoneId,
                                'user_id' => $author->id,
                                'parent_comment_id' => null,
                                'comment_text' => $commentTemplate['text'],
                            ],
                            [
                                'milestone_id' => $legacyMilestoneId,
                                'user_id' => $author->id,
                                'parent_comment_id' => null,
                                'comment_text' => $commentTemplate['text'],
                                'created_at' => now()->subDays(10 - $commentIndex),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }

                if ($milestoneId !== null) {
                    $milestoneIds[] = $milestoneId;
                }
            }

            $existingMentorshipId = DB::table('mentorship')
                ->where('mentor_user_id', $mentor->id)
                ->where('application_id', $application->id)
                ->value('id');

            if ($existingMentorshipId !== null) {
                $scheduledAt = now()->subDays(3 - $applicationIndex)->toDateTimeString();

                DB::table('mentorship_session')->updateOrInsert(
                    [
                        'mentorship_id' => $existingMentorshipId,
                        'created_by' => $mentor->id,
                        'scheduled_at' => $scheduledAt,
                    ],
                    [
                        'mentorship_id' => $existingMentorshipId,
                        'created_by' => $mentor->id,
                        'title' => 'Pravidelná konzultácia k projektu',
                        'type' => 'offline',
                        'meeting_url' => null,
                        'scheduled_at' => $scheduledAt,
                        'agenda' => 'Stabilný demo záznam konzultácie pre mentor dashboard.',
                        'status' => 'completed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
