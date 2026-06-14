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
        // 1. Načítanie demo mentora
        $mentor = User::query()->where('email', DemoMentorUserSeeder::EMAIL)->first();

        // 2. Načítanie aplikácií / projektov
        $applications = Application::query()
            ->with(['creator:id,name,surname,email'])
            ->orderBy('id')
            ->take(3)
            ->get();

        if (! $mentor || $applications->isEmpty()) {
            return;
        }

        // 3. Vytiahnutie ID stavov priamo zo seederu podľa názvu
        $statuses = DB::table('milestone_status')->pluck('id', 'name')->toArray();

        $plannedId    = $statuses['Plánované'] ?? 1;
        $inProgressId = $statuses['V riešení'] ?? 2;
        $completedId  = $statuses['Dokončené'] ?? 3;  // pending_approval na frontende
        $approvedId   = $statuses['Schválené'] ?? 4;   // completed na frontende
        $rejectedId   = $statuses['Zamietnuté'] ?? 5;
        $returnedId   = $statuses['Vrátené na doplnenie'] ?? 6;

        // 4. Rozšírené šablóny míľnikov (Pokryjú kompletne celú stavovú mašinu)
        $milestoneTemplates = [
            [
                'name' => '1. Analýza požiadaviek a špecifikácia',
                'start_date' => now(),
                'deadline' => now()->subDays(5),
                'status_id' => $approvedId,
                'comments' => [
                    ['author' => 'mentor', 'text' => 'Analýza schválená. Zadanie spĺňa všetky biznis požiadavky inkubátora.'],
                ],
            ],
            [
                'name' => '2. Architektonický návrh a ERD',
                'start_date' => now(),
                'deadline' => now()->addDays(4),
                'status_id' => $approvedId,
                'comments' => [
                    ['author' => 'creator', 'text' => 'Posielame prvý návrh diagramov.'],
                    ['author' => 'mentor', 'text' => 'Chýba vám tam relácia medzi aplikáciou a výzvou cez call_id. Prerobte a doplňte to prosím.'],
                ],
            ],
            [
                'name' => '3. Vývoj základného MVP',
                'start_date' => now(),
                'deadline' => now()->addWeeks(2),
                'status_id' => $inProgressId,
                'comments' => [
                    ['author' => 'creator', 'text' => 'MVP prototyp je nasadený na stagingu. Všetky základné CRUD operácie fungujú.'],
                ],
            ],
            [
                'name' => '4. API Integrácia a Autentifikácia',
                'start_date' => now(),
                'deadline' => now()->addWeeks(5),
                'status_id' => $plannedId,
                'comments' => []
            ],
            [
                'name' => '5. Finálne testovanie a nasadenie',
                'start_date' => now(),
                'deadline' => now()->addWeeks(6),
                'status_id' => $plannedId, // 💎 PLÁNOVANÉ (Sivý zamknutý míľnik, mentor ho môže odomknúť ak sú predošlé hotové)
                'comments' => [],
            ],
        ];

        foreach ($applications as $applicationIndex => $application) {
            // Prepojenie mentorship väzby
            DB::table('mentorship')->updateOrInsert(
                [
                    'mentor_user_id' => $mentor->id,
                    'application_id' => $application->id,
                ],
                [
                    'mentor_user_id' => $mentor->id,
                    'application_id' => $application->id,
                    'created_at' => now()->subDays(15 - $applicationIndex),
                    'updated_at' => now(),
                ]
            );

            // Pridaj komentáre k existujúcim míľnikom (vytvorené DemoProjectSeederom)
            $existingMilestones = DB::table('project_milestones')
                ->where('call_id', $application->call_id)
                ->orderBy('id')
                ->get();

            foreach ($milestoneTemplates as $milestoneIndex => $template) {
                if (empty($template['comments'])) continue;

                $milestone = $existingMilestones->get($milestoneIndex);
                if (! $milestone) continue;

                foreach ($template['comments'] as $commentIndex => $commentTemplate) {
                    $authorKey = $commentTemplate['author'] ?? 'creator';
                    $author    = $authorKey === 'mentor'
                        ? $mentor
                        : ($application->creator ?? $mentor);

                    DB::table('milestone_comments')->updateOrInsert(
                        [
                            'milestone_id' => $milestone->id,
                            'comment_text' => $commentTemplate['text'],
                        ],
                        [
                            'milestone_id'      => $milestone->id,
                            'user_id'           => $author->id,
                            'parent_comment_id' => null,
                            'comment_text'      => $commentTemplate['text'],
                            'created_at'        => now()->subDays(5 - $commentIndex),
                            'updated_at'        => now(),
                        ]
                    );
                }
            }

            // Zachovanie logovania konzultácií
            $existingMentorshipId = DB::table('mentorship')
                ->where('mentor_user_id', $mentor->id)
                ->where('application_id', $application->id)
                ->value('id');

            if ($existingMentorshipId !== null) {
                $scheduledAt = now()->subDays(3 - $applicationIndex)->toDateTimeString();

                DB::table('mentorship_session')->updateOrInsert(
                    [
                        'mentorship_id' => $existingMentorshipId,
                        'created_by'   => $mentor->id,
                        'scheduled_at' => $scheduledAt,
                    ],
                    [
                        'mentorship_id' => $existingMentorshipId,
                        'created_by'   => $mentor->id,
                        'title'        => 'Pravidelná konzultácia k projektu',
                        'type'         => 'offline',
                        'meeting_url'  => null,
                        'scheduled_at' => $scheduledAt,
                        'agenda'       => 'Stabilný demo záznam konzultácie pre mentor dashboard.',
                        'status'       => 'completed',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                );
            }
        }
    }
}
