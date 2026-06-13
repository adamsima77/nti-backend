<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationRole;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\Role;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Criterion;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;
use Modules\Programs\Models\QualificationStack;

class CallSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Načítanie základných väzieb
        $organization = Organization::query()->first();
        $publicCallType = CallType::query()->where('name', 'Verejná výzva')->first();
        $publishedStatus = StatusOfCall::query()->where('name', 'Publikované')->first()
            ?? StatusOfCall::query()->where('name', 'LIKE', '%realiz%')->first();

        $qStack = QualificationStack::query()->first(); // Pre Program A

        if (!$organization || !$publicCallType || !$publishedStatus) {
            $this->command->error('Chýbajú základné dáta (Organizácia, Typ výzvy alebo Status).');
            return;
        }
        
        $partnerRole = Role::where('name', 'partner')->first();
        $orgPoRole = OrganizationRole::where('name', 'org_product_owner')->first();

        $poUsersData = [
            ['email' => 'peter.owner@slovakinnovation.sk',  'name' => 'Peter',   'surname' => 'Garant',    'job_position' => 'Innovation Manager'],
            ['email' => 'jana.novakova@techpartner.sk',     'name' => 'Jana',    'surname' => 'Nováková',  'job_position' => 'Product Owner'],
            ['email' => 'martin.kral@digitalworks.sk',      'name' => 'Martin',  'surname' => 'Kráľ',      'job_position' => 'CTO'],
            ['email' => 'lucia.horakova@smartsystems.sk',   'name' => 'Lucia',   'surname' => 'Horáková',  'job_position' => 'R&D Lead'],
        ];

        $poUsers = [];
        foreach ($poUsersData as $poData) {
            $user = User::query()->updateOrCreate(
                ['email' => $poData['email']],
                [
                    'name'         => $poData['name'],
                    'surname'      => $poData['surname'],
                    'password'     => Hash::make('Password123!'),
                    'status_id'    => \Modules\IdentityAccess\Enums\UserStatus::ACTIVE->value,
                    'job_position' => $poData['job_position'],
                ]
            );
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();

            if ($partnerRole) {
                $user->roles()->syncWithoutDetaching([$partnerRole->id]);
            }

            if ($orgPoRole) {
                $organization->users()->syncWithoutDetaching([
                    $user->id => ['organization_role' => $orgPoRole->id],
                ]);
            }

            $poUsers[] = $user;
        }

        $criteria = Criterion::query()->pluck('id')->all();
        $programs = Program::query()->whereIn('type_of_program_id', [1, 2])->get()->keyBy('type_of_program_id');

        if ($programs->isEmpty()) {
            $this->command->error('Žiadne programy s type_of_program_id 1 alebo 2 neboli nájdené.');
            return;
        }

        // ── FORM SCHÉMA PRE PROGRAM A (Opravené: vrátené povinné 'id' pre každé pole) ──
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

        // ── FORM SCHÉMA PRE PROGRAM B (Opravené: vrátené povinné 'id' pre každé pole) ──
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

        // 3. Definícia 5 výziev pre rok 2026
        $callsData = [
            // ── PROGRAM A (type_of_program_id: 1) ─────────────────────────────────
            [
                'program_id' => isset($programs[1]) ? $programs[1]->id : $programs->first()->id,
                'is_program_b' => false,
                'name_sk' => 'Výzva 2026 - Akcelerátor študentských nápadov (Program A)',
                'name_en' => 'Call 2026 - Student Idea Accelerator (Program A)',
                'desc_sk' => 'Podpora vlastných inovatívnych projektov študentských tímov s cieľom validovať biznis model.',
                'desc_en' => 'Support for custom innovative projects of student teams with the goal of validating their business model.',
                'app_start' => now()->subDays(5),
                'app_deadline' => now()->addWeeks(3),
                'proj_start' => now()->addMonths(1),
                'proj_end' => now()->addMonths(7),
            ],
            [
                'program_id' => isset($programs[1]) ? $programs[1]->id : $programs->first()->id,
                'is_program_b' => false,
                'name_sk' => 'Výzva 2026 - Výskum a Vývoj na Univerzitách (Program A)',
                'name_en' => 'Call 2026 - R&D at Universities (Program A)',
                'desc_sk' => 'Podpora technologických akademických tímov a prepojenie výskumu s mikro-podnikaním.',
                'desc_en' => 'Support for technological academic teams and connecting research with micro-entrepreneurship.',
                'app_start' => now()->subDays(1),
                'app_deadline' => now()->addMonths(1),
                'proj_start' => now()->addMonths(2),
                'proj_end' => now()->addMonths(8),
            ],

            // ── PROGRAM B (type_of_program_id: 2) ─────────────────────────────────
            [
                'program_id' => isset($programs[2]) ? $programs[2]->id : $programs->first()->id,
                'is_program_b' => true,
                'po_user_index' => 1,
                'name_sk' => 'Výzva 2026 - AI Asistent pre Smart Cities',
                'name_en' => 'Call 2026 - AI Assistant for Smart Cities',
                'desc_sk' => 'Riešenie reálneho zadania od priemyselných partnerov platformy. Implementácia LLM modelov.',
                'desc_en' => 'Solving a real-world challenge from industrial partners. Implementation of LLM models.',
                'app_start' => now()->subDays(3),
                'app_deadline' => now()->addMonths(1)->addWeeks(2),
                'proj_start' => now()->addMonths(3),
                'proj_end' => now()->addMonths(9),
                'budget' => 3000.00,
                'budget_type' => 'Po míľnikoch',
                'tech_spec' => 'Požaduje sa skúsenosť s Pythonom, deploymentom modelov na AWS a integráciou cez REST API.',
                'tech_tags' => ['Python', 'PyTorch', 'FastAPI', 'Next.js', 'OpenAI API'],
            ],
            [
                'program_id' => isset($programs[2]) ? $programs[2]->id : $programs->first()->id,
                'is_program_b' => true,
                'po_user_index' => 2,
                'name_sk' => 'Výzva 2026 - Logistická Optimalizácia',
                'name_en' => 'Call 2026 - Logistic Optimization',
                'desc_sk' => 'Zadanie zamerané na prediktívnu analýzu dodávateľských reťazcov a trasovanie vozidiel v reálnom čase.',
                'desc_en' => 'Challenge focused on predictive analysis of supply chains and real-time vehicle routing.',
                'app_start' => now()->addDays(10),
                'app_deadline' => now()->addMonths(2),
                'proj_start' => now()->addMonths(3)->addWeeks(2),
                'proj_end' => now()->addMonths(10),
                'budget' => 1800.00,
                'budget_type' => 'Mesačne',
                'call_status' => 'Uzavreté',
                'tech_spec' => 'Algoritmus musí zvládnuť spracovanie veľkého množstva geodát s nízkou latenciou.',
                'tech_tags' => ['Go', 'TypeScript', 'Node.js', 'Redis', 'Docker'],
            ],
            [
                'program_id' => isset($programs[2]) ? $programs[2]->id : $programs->first()->id,
                'is_program_b' => true,
                'po_user_index' => 3,
                'name_sk' => 'Výzva 2026 - Kybernetická Bezpečnosť',
                'name_en' => 'Call 2026 - Cybersecurity Frameworks',
                'desc_sk' => 'Návrh a testovanie bezpečnostných auditných mechanizmov pre cloudové infraštruktúry podnikov.',
                'desc_en' => 'Design and testing of security audit mechanisms for corporate cloud infrastructures.',
                'app_start' => now()->subDays(5),
                'app_deadline' => now()->addMonths(3),
                'proj_start' => now()->addMonths(4),
                'proj_end' => now()->addMonths(10),
                'budget' => 2500.00,
                'budget_type' => 'Po odovzdaní',
                'tech_spec' => 'Očakáva sa implementácia skriptov pre penetračné testovanie s dokumentáciou podľa ISO 27001.',
                'tech_tags' => ['Rust', 'Python', 'Linux', 'Kubernetes', 'Wireshark'],
            ],
        ];

        // 4. Spracovanie a zápis dát do databázy
        foreach ($callsData as $data) {

            $attributes = [
                'program_id'              => $data['program_id'],
                'description'             => $data['desc_sk'],
                'application_start'       => $data['app_start'],
                'application_deadline'    => $data['app_deadline'],
                'project_start'           => $data['proj_start'],
                'project_end'             => $data['proj_end'],
                'call_type_id'            => $publicCallType->id,
                'force_closed'            => false,
                'application_form_schema' => $data['is_program_b'] ? $formSchemaProgramB : $formSchemaProgramA,
            ];

            if ($data['is_program_b']) {
                $attributes['organization_id']         = $organization->id;
                $attributes['po_user_id']             = $poUsers[$data['po_user_index'] ?? 0]->id;
                $attributes['budget']                 = $data['budget'];
                $attributes['budget_type']            = $data['budget_type'];
                $attributes['tech_spec']              = $data['tech_spec'];
                $attributes['tech_tags']              = $data['tech_tags'];

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

            // Vyhľadávanie a zápis podľa program_id + mena výzvy (rovnaký kľúč ako DemoProjectSeeder)
            $call = Call::updateOrCreate(
                [
                    'program_id' => $data['program_id'],
                    'name'       => $data['name_sk'],
                ],
                $attributes
            );

            // Synchronizácia kritérií (OPRAVENÉ: váha znížená z 20.00 na povolených max 10.00)
            if (!empty($criteria)) {
                $syncData = [];
                foreach ($criteria as $criterionId) {
                    $syncData[$criterionId] = [
                        'weight' => 5, // Splnené: hodnota nepresahuje limit 10
                        'is_academic_signal' => false
                    ];
                }
                $call->callCriteria()->sync($syncData);
            }

            $callStatus = isset($data['call_status'])
                ? StatusOfCall::query()->where('name', $data['call_status'])->first() ?? $publishedStatus
                : $publishedStatus;
            StatusOfCallHasCall::query()->where('call_id', $call->id)->delete();
            StatusOfCallHasCall::query()->create([
                'call_id'           => $call->id,
                'status_of_call_id' => $callStatus->id,
                'note'              => 'Inicializované seederom. Plne validné schémy aj váhy kritérií.',
            ]);

            $call->callTranslations()->updateOrCreate(
                ['language_id' => 1],
                ['name' => $data['name_sk'], 'description' => $data['desc_sk']]
            );

            $call->callTranslations()->updateOrCreate(
                ['language_id' => 2],
                ['name' => $data['name_en'], 'description' => $data['desc_en']]
            );
        }
    }
}
