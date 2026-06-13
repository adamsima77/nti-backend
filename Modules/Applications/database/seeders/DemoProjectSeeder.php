<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Criterion;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;
use Modules\Programs\Models\QualificationStack;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Načítanie základných väzieb
        $organization = Organization::query()->first();
        $publicCallType = CallType::query()->where('name', 'Verejná výzva')->first();
        $publishedStatus = StatusOfCall::query()->where('name', 'Publikované')->firstOrFail();
        $realizaciaStatus = StatusOfCall::query()->where('name', 'V realizácii')->firstOrFail();

        $qStack = QualificationStack::query()->first(); // Pre Program A

        if (!$organization || !$publicCallType || !$publishedStatus || !$realizaciaStatus) {
            return;
        }

        $mentorRole = Role::query()->where('name', 'mentor')->first();
        $studentRole = Role::query()->where('name', 'student')->first();
        $leaderTeamRole = TeamRole::query()->where('name', 'Vedúci tímu')->first();
        $memberTeamRole = TeamRole::query()->where('name', 'Člen tímu')->first();

        $programA = Program::query()->where('type_of_program_id', 1)->first();
        $programB = Program::query()->where('type_of_program_id', 2)->first();
        $criteria = Criterion::query()->pluck('id')->all();

        if (!$programA || !$programB || !$mentorRole || !$studentRole || !$leaderTeamRole || !$memberTeamRole) {
            return;
        }

        // 2. Vytvorenie / Seedovanie špecifického Product Ownera (Pre Program B)
        $poUser = User::query()->updateOrCreate(
            ['email' => 'peter.owner@slovakinnovation.sk'],
            [
                'name'         => 'Peter',
                'surname'      => 'Garant',
                'password'     => Hash::make('Password123!'),
                'status_id'    => UserStatus::ACTIVE->value,
                'job_position' => 'Innovation Manager',
            ]
        );
        $partnerRole = Role::where('name', 'partner')->first();
        if ($partnerRole) {
            $poUser->roles()->syncWithoutDetaching([$partnerRole->id]);
        }

        $orgPoRole = OrganizationRole::where('name', 'org_product_owner')->first();
        if ($orgPoRole && !$organization->users()->where('user_id', $poUser->id)->exists()) {
            $organization->users()->attach($poUser->id, [
                'organization_role' => $orgPoRole->id,
            ]);
        }

        // ── FORM SCHÉMA PRE PROGRAM A ──
        $formSchemaProgramA = [
            'title' => 'Formulár žiadosti - Program A (Grantový program)',
            'description' => 'Prosím, nahrajte všetky povinné materiály pre validáciu vášho inovatívneho biznis nápadu.',
            'fields' => [
                [
                    'id' => 'doc_executive_summary',
                    'name' => 'executive_summary',
                    'type' => 'file',
                    'label' => 'Executive Summary',
                    'help_text' => 'Stručný opis problému, riešenia, trhu a prínosu.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_technical_architecture',
                    'name' => 'technical_architecture',
                    'type' => 'file',
                    'label' => 'Technická architektúra',
                    'help_text' => 'Diagram a opis systémovej architektúry riešenia.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_roadmap',
                    'name' => 'roadmap',
                    'type' => 'file',
                    'label' => 'Roadmapa',
                    'help_text' => 'Harmonogram realizácie fáz a kľúčových míľnikov.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx,.xls,.xlsx',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_budget',
                    'name' => 'budget',
                    'type' => 'file',
                    'label' => 'Rozpočet projektu',
                    'help_text' => 'Detailný finančný rozpis čerpania grantu.',
                    'required' => true,
                    'accept' => '.pdf,.xls,.xlsx',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_risk_analysis',
                    'name' => 'risk_analysis',
                    'type' => 'file',
                    'label' => 'Riziková analýza',
                    'help_text' => 'Identifikácia biznisových a technických hrozieb.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_monetization_model',
                    'name' => 'monetization_model',
                    'type' => 'file',
                    'label' => 'Monetizačný model',
                    'help_text' => 'Spôsob vytvárania hodnoty a príjmov produktu.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx',
                    'documentUpload' => true
                ]
            ]
        ];

        // ── FORM SCHÉMA PRE PROGRAM B ──
        $formSchemaProgramB = [
            'title' => 'Formulár prihlášky na výzvu - Program B (Zadania partnerov)',
            'description' => 'Pre zapojenie vášho tímu do výzvy priemyselného partnera nahrajte požadované profily a návrh konceptu.',
            'fields' => [
                [
                    'id' => 'doc_team_cvs',
                    'name' => 'team_cvs',
                    'type' => 'file',
                    'label' => 'Životopisy členov tímu (CV)',
                    'help_text' => 'Nahrajte životopisy členov tímu (spojené do jedného PDF alebo ako archív ZIP).',
                    'required' => true,
                    'accept' => '.pdf,.zip',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_motivation_letter',
                    'name' => 'motivation_letter',
                    'type' => 'file',
                    'label' => 'Motivačný list',
                    'help_text' => 'Dôvody, prečo chce váš tím pracovať práve na tomto zadaní.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx',
                    'documentUpload' => true
                ],
                [
                    'id' => 'doc_solution_proposal',
                    'name' => 'solution_proposal',
                    'type' => 'file',
                    'label' => 'Predbežný návrh riešenia',
                    'help_text' => 'Prvotný technologický alebo biznisový pohľad na riešenie zadania.',
                    'required' => true,
                    'accept' => '.pdf,.doc,.docx',
                    'documentUpload' => true
                ]
            ]
        ];

        // 3. Definícia demo projektov a ich prislúchajúcich výziev
        $projectDefinitions = [
            [
                'call_name' => 'EcoTrack – Sledovanie uhlíkovej stopy',
                'program' => $programA,
                'is_program_b' => false,
                'team_name' => 'GreenTech tím',
                'description' => 'Projekt na sledovanie uhlíkovej stopy a návrhy na úsporu energie.',
                'description_en' => 'Project for tracking carbon footprint and energy saving proposals.',
                'status' => 'V hodnotení',
                'team' => [
                    ['email' => 'greentech.lead@test.nti.local', 'name' => 'Eva', 'surname' => 'Mrázová', 'role' => 'Vedúci tímu'],
                    ['email' => 'greentech.dev@test.nti.local', 'name' => 'Tomáš', 'surname' => 'Hronec', 'role' => 'Člen tímu'],
                    ['email' => 'greentech.qa@test.nti.local', 'name' => 'Lucia', 'surname' => 'Bieliková', 'role' => 'Člen tímu'],
                ],
            ],
            [
                'call_name' => 'AI chatbot pre zákaznícku podporu',
                'po_email'  => 'peter.owner@slovakinnovation.sk',
                'program' => $programB,
                'is_program_b' => true,
                'team_name' => 'DataPulse',
                'description' => 'Chatbot pre zákaznícku podporu s integráciou AI odpovedí.',
                'description_en' => 'Chatbot for customer support with AI response integration.',
                'status' => 'Zamietnuté',
                'call_status' => 'V realizácii',
                'milestones' => [
                    ['name' => 'Návrh architektúry a výber LLM modelu',    'start' => '-90 days', 'deadline' => '-70 days', 'status' => 'Schválené'],
                    ['name' => 'Implementácia chatbot jadra a REST API',    'start' => '-69 days', 'deadline' => '-40 days', 'status' => 'Schválené'],
                    ['name' => 'Integrácia do zákazníckeho portálu a UAT', 'start' => '-39 days', 'deadline' => '+2 days', 'status' => 'V riešení'],
                ],
                'budget' => 2000.00,
                'budget_type' => 'Po míľnikoch',
                'tech_spec' => 'Požaduje sa integrácia LLM modelov cez REST API a základná znalosť Pythonu/Node.js.',
                'tech_tags' => ['Python', 'Node.js', 'OpenAI API', 'Vue.js'],
                'team' => [
                    ['email' => 'datapulse.lead@test.nti.local', 'name' => 'Rastislav', 'surname' => 'Fedor', 'role' => 'Vedúci tímu'],
                    ['email' => 'datapulse.dev@test.nti.local', 'name' => 'Simona', 'surname' => 'Krajčí', 'role' => 'Člen tímu'],
                ],
            ],
            [
                'call_name' => 'Výzva 2026 - Logistická Optimalizácia',
                'po_email'  => 'martin.kral@digitalworks.sk',
                'program' => $programB,
                'is_program_b' => true,
                'team_name' => 'LogiCore',
                'description' => 'Zadanie zamerané na prediktívnu analýzu dodávateľských reťazcov a trasovanie vozidiel v reálnom čase.',
                'description_en' => 'Challenge focused on predictive analysis of supply chains and real-time vehicle routing.',
                'status' => 'Ukončené',
                'call_status' => 'Uzavreté',
                'budget' => 1800.00,
                'budget_type' => 'Mesačne',
                'tech_spec' => 'Algoritmus musí zvládnuť spracovanie veľkého množstva geodát s nízkou latenciou.',
                'tech_tags' => ['Go', 'TypeScript', 'Node.js', 'Redis', 'Docker'],
                'milestones' => [
                    ['name' => 'Analýza požiadaviek a návrh architektúry', 'start' => '-120 days', 'deadline' => '-90 days', 'status' => 'Schválené'],
                    ['name' => 'Implementácia prediktívneho modulu',        'start' => '-89 days',  'deadline' => '-60 days', 'status' => 'Schválené'],
                    ['name' => 'Integrácia GPS trasovania v reálnom čase',  'start' => '-59 days',  'deadline' => '-30 days', 'status' => 'Schválené'],
                    ['name' => 'Testovanie a odovzdanie záverečnej správy', 'start' => '-29 days',  'deadline' => '-5 days',  'status' => 'Schválené'],
                ],
                'team' => [
                    ['email' => 'logicore.lead@test.nti.local',  'name' => 'Andrej',   'surname' => 'Mináč',    'role' => 'Vedúci tímu'],
                    ['email' => 'logicore.dev@test.nti.local',   'name' => 'Kristína', 'surname' => 'Polláková', 'role' => 'Člen tímu'],
                    ['email' => 'logicore.data@test.nti.local',  'name' => 'Ondrej',   'surname' => 'Takáč',    'role' => 'Člen tímu'],
                ],
            ],
            [
                'call_name' => 'Výzva 2026 - Logistická Optimalizácia',
                'po_email'  => 'martin.kral@digitalworks.sk',
                'program' => $programB,
                'is_program_b' => true,
                'team_name' => 'RouteX',
                'description' => 'Zadanie zamerané na prediktívnu analýzu dodávateľských reťazcov a trasovanie vozidiel v reálnom čase.',
                'description_en' => 'Challenge focused on predictive analysis of supply chains and real-time vehicle routing.',
                'status' => 'Zamietnuté',
                'call_status' => 'Uzavreté',
                'budget' => 1800.00,
                'budget_type' => 'Mesačne',
                'tech_spec' => 'Algoritmus musí zvládnuť spracovanie veľkého množstva geodát s nízkou latenciou.',
                'tech_tags' => ['Go', 'TypeScript', 'Node.js', 'Redis', 'Docker'],
                'team' => [
                    ['email' => 'routex.lead@test.nti.local', 'name' => 'Dominika', 'surname' => 'Čierna',  'role' => 'Vedúci tímu'],
                    ['email' => 'routex.dev@test.nti.local',  'name' => 'Lukáš',    'surname' => 'Sedlák',  'role' => 'Člen tímu'],
                ],
            ],
            [
                'call_name' => 'StudyBuddy – AI asistent',
                'program' => $programA,
                'is_program_b' => false,
                'team_name' => 'EduTech',
                'description' => 'AI asistent pre štúdium a organizáciu školských úloh.',
                'description_en' => 'AI assistant for studying and school task management.',
                'status' => 'Schválené',
                'team' => [
                    ['email' => 'study.lead@test.nti.local', 'name' => 'Zuzana', 'surname' => 'Bartošová', 'role' => 'Vedúci tímu'],
                    ['email' => 'study.mobile@test.nti.local', 'name' => 'Filip', 'surname' => 'Bako', 'role' => 'Člen tímu'],
                    ['email' => 'study.ml@test.nti.local', 'name' => 'Jakub', 'surname' => 'Moravčík', 'role' => 'Člen tímu'],
                ],
            ],
        ];

        // 4. Spracovanie dát a zápis do databázy
        foreach ($projectDefinitions as $index => $definition) {

            $attributes = [
                'description'             => $definition['description'],
                'application_start'       => now()->subDays(10 - $index),
                'application_deadline'    => now()->addWeeks(2 + $index),
                'project_start'           => now()->addMonths(1),
                'project_end'             => now()->addMonths(6),
                'call_type_id'            => $publicCallType->id,
                'force_closed'            => false,
                'application_form_schema' => $definition['is_program_b'] ? $formSchemaProgramB : $formSchemaProgramA,
            ];

            // Aplikovanie vetvenia na základe typu programu
            if ($definition['is_program_b']) {
                $callPoUser = isset($definition['po_email'])
                    ? User::query()->where('email', $definition['po_email'])->first() ?? $poUser
                    : $poUser;
                $attributes['organization_id']         = $organization->id;
                $attributes['po_user_id']             = $callPoUser->id;
                $attributes['budget']                 = $definition['budget'] ?? 0.00;
                $attributes['budget_type']            = $definition['budget_type'] ?? 'Po míľnikoch';
                $attributes['tech_spec']              = $definition['tech_spec'] ?? null;
                $attributes['tech_tags']              = $definition['tech_tags'] ?? null;
                $attributes['qualification_stack_id'] = null;
            } else {
                $attributes['organization_id']         = null;
                $attributes['po_user_id']             = null;
                $attributes['budget']                 = 0.00;
                $attributes['budget_type']            = 'Po míľnikoch';
                $attributes['tech_spec']              = null;
                $attributes['tech_tags']              = null;
                $attributes['qualification_stack_id'] = $qStack?->id;
            }

            // Vytvorenie / Aktualizácia Výzvy
            $call = Call::query()->updateOrCreate(
                [
                    'program_id' => $definition['program']->id,
                    'name'       => $definition['call_name'],
                ],
                $attributes
            );

            // Synchronizácia kritérií s fixnou povolenou váhou 5
            if (!empty($criteria)) {
                $syncData = [];
                foreach ($criteria as $criterionId) {
                    $syncData[$criterionId] = [
                        'weight' => 5,
                        'is_academic_signal' => false
                    ];
                }
                $call->callCriteria()->sync($syncData);
            }

            // Priradenie statusu výzvy
            $callStatus = isset($definition['call_status'])
                ? StatusOfCall::query()->where('name', $definition['call_status'])->first() ?? $publishedStatus
                : $publishedStatus;
            StatusOfCallHasCall::query()->where('call_id', $call->id)->delete();
            StatusOfCallHasCall::query()->create([
                'call_id'           => $call->id,
                'status_of_call_id' => $callStatus->id,
                'note'              => 'Demo projektový call. Plne validné schémy aj váhy kritérií.',
            ]);

            // Jazykové preklady (SK / EN)
            $call->callTranslations()->updateOrCreate(
                ['language_id' => 1],
                ['name' => $definition['call_name'], 'description' => $definition['description']]
            );

            $call->callTranslations()->updateOrCreate(
                ['language_id' => 2],
                ['name' => $definition['call_name'], 'description' => $definition['description_en']]
            );

            // 5. Seedovanie Tímu a Členov
            $team = Team::query()->updateOrCreate(
                ['name' => $definition['team_name']],
                ['name' => $definition['team_name']]
            );

            foreach ($definition['team'] as $memberIndex => $memberData) {
                $user = User::query()->updateOrCreate(
                    ['email' => $memberData['email']],
                    [
                        'name'         => $memberData['name'],
                        'surname'      => $memberData['surname'],
                        'password'     => Hash::make('Password123!'),
                        'status_id'    => UserStatus::ACTIVE->value,
                        'job_position' => $memberIndex === 0 ? 'Vedúci tímu (demo)' : 'Člen tímu (demo)',
                    ]
                );

                $user->forceFill(['email_verified_at' => now()])->saveQuietly();
                $user->roles()->syncWithoutDetaching([$studentRole->id]);

                $teamRoleId = $memberData['role'] === 'Vedúci tímu' ? $leaderTeamRole->id : $memberTeamRole->id;

                DB::table('team_members')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                    ],
                    [
                        'user_id'      => $user->id,
                        'team_id'      => $team->id,
                        'team_role_id' => $teamRoleId,
                    ]
                );
            }

            $creator = User::query()->where('email', $definition['team'][0]['email'])->first();
            if (!$creator) {
                continue;
            }

            // 6. Vytvorenie samotnej prihlášky (Application)
            $statusId = StatusOfApplication::query()->where('name', $definition['status'])->value('id')
                ?? StatusOfApplication::query()->where('name', 'Podané')->value('id');

            $application = Application::query()->updateOrCreate(
                [
                    'call_id' => $call->id,
                    'team_id' => $team->id,
                ],
                [
                    'submitted_at'  => now()->subDays(7 - $index),
                    'last_update'   => now()->subDays(1),
                    'created_by'    => $creator->id,
                    'active_status' => $statusId,
                ]
            );
            $application->statusHistory()->delete();
            $application->statusHistory()->create([
                'status_of_application_id' => $statusId,
                'note' => $definition['status'],
                'changed_by' => null,
            ]);

            if (!empty($definition['milestones'])) {
                $milestoneIds = DB::table('project_milestones')->where('call_id', $call->id)->pluck('id');
                DB::table('milestone_comments')->whereIn('milestone_id', $milestoneIds)->delete();
                DB::table('document_has_milestone')->whereIn('milestone_id', $milestoneIds)->delete();
                DB::table('project_milestones')->where('call_id', $call->id)->delete();
                foreach ($definition['milestones'] as $m) {
                    $milestoneStatusId = DB::table('milestone_status')->where('name', $m['status'])->value('id') ?? 1;
                    DB::table('project_milestones')->insert([
                        'name'                => $m['name'],
                        'start_date'          => now()->modify($m['start'])->toDateString(),
                        'deadline'            => now()->modify($m['deadline'])->toDateString(),
                        'milestone_status_id' => $milestoneStatusId,
                        'call_id'             => $call->id,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            }
        }

        // Tím NovakDev + prihláška pre jan.novak@test.nti.local
        $novakUser = User::query()->where('email', 'jan.novak@test.nti.local')->first();
        $aiChatbotCall = Call::query()->where('name', 'AI chatbot pre zákaznícku podporu')->first();

        if ($novakUser && $aiChatbotCall) {
            $novakTeam = Team::query()->firstOrCreate(['name' => 'NovakDev']);

            $novakMembersData = [
                ['email' => 'jan.novak@test.nti.local',         'name' => 'Ján',    'surname' => 'Novák',   'role' => 'leader'],
                ['email' => 'novakdev.member1@test.nti.local',  'name' => 'Martin', 'surname' => 'Horváth', 'role' => 'member'],
                ['email' => 'novakdev.member2@test.nti.local',  'name' => 'Petra',  'surname' => 'Vlčková', 'role' => 'member'],
            ];

            foreach ($novakMembersData as $md) {
                $member = User::query()->updateOrCreate(
                    ['email' => $md['email']],
                    [
                        'name'      => $md['name'],
                        'surname'   => $md['surname'],
                        'password'  => Hash::make('Password123!'),
                        'status_id' => UserStatus::ACTIVE->value,
                    ]
                );
                $member->forceFill(['email_verified_at' => now()])->saveQuietly();
                $member->roles()->syncWithoutDetaching([$studentRole->id]);
                \Modules\Students\Models\Student::query()->firstOrCreate(['user_id' => $member->id]);

                $teamRoleId = $md['role'] === 'leader' ? $leaderTeamRole->id : $memberTeamRole->id;
                DB::table('team_members')->updateOrInsert(
                    ['user_id' => $member->id, 'team_id' => $novakTeam->id],
                    ['user_id' => $member->id, 'team_id' => $novakTeam->id, 'team_role_id' => $teamRoleId]
                );
            }

            $aktivnyStatus = StatusOfApplication::query()->where('name', 'Aktívny projekt')->first();
            if ($aktivnyStatus) {
                $novakApp = Application::query()->updateOrCreate(
                    ['call_id' => $aiChatbotCall->id, 'team_id' => $novakTeam->id],
                    [
                        'submitted_at'  => now()->subDays(10),
                        'last_update'   => now()->subDays(1),
                        'created_by'    => $novakUser->id,
                        'active_status' => $aktivnyStatus->id,
                    ]
                );
                $novakApp->statusHistory()->delete();
                $novakApp->statusHistory()->create([
                    'status_of_application_id' => $aktivnyStatus->id,
                    'note'       => 'Aktívny projekt',
                    'changed_by' => null,
                ]);
            }
        }
    }
}
