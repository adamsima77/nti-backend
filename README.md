# NTI Backend – Hlavná Dokumentácia

> **Laravel 12** | **PHP 8.2+** | **PostgreSQL 15+** | **Redis 7+** | **Laravel Sanctum**

---

## Obsah

1. [O projekte](#o-projekte)
2. [Technologický stack](#technologický-stack)
3. [Modulárna architektúra](#modulárna-architektúra)
4. [Databázová schéma – prehľad](#databázová-schéma)
5. [Autentifikácia a autorizácia](#autentifikácia-a-autorizácia)
6. [Stavový automat žiadostí](#stavový-automat-žiadostí)
7. [Asynchrónne spracovanie](#asynchrónne-spracovanie)
8. [Servisná vrstva](#servisná-vrstva)
9. [Závislosť medzi modulmi](#závislosť-medzi-modulmi)
10. [Inštalácia a konfigurácia](#inštalácia-a-konfigurácia)
11. [Prehľad API endpointov](#prehľad-api-endpointov)
12. [Kľúčové konvencie](#kľúčové-konvencie)

---

## O projekte

**NTI Backend** je REST API server pre platformu NTI (Nitriansky Technologický Inkubátor). Platforma umožňuje:

- Registráciu a správu študentov a partnerských organizácií
- Podávanie a správu grantových žiadostí na výzvy
- Odborné hodnotenie žiadostí hodnotiacimi komisiami
- Mentorstvo schválených projektov
- Generovanie reportov, exportov a GDPR dokumentácie
- Správu CMS obsahu verejnej stránky

Projekt je postavený na **Laravel 12** s modulárnou architektúrou cez balíček `nwidart/laravel-modules`.

---

## Technologický stack

| Komponent | Technológia | Verzia |
|-----------|------------|--------|
| Framework | Laravel | 12.x |
| PHP | PHP | 8.2+ |
| Databáza | PostgreSQL | 15+ |
| Cache / Queue | Redis | 7+ |
| Autentifikácia | Laravel Sanctum | – |
| Role/Permissions | spatie/laravel-permission | – |
| PDF generovanie | barryvdh/laravel-dompdf | – |
| Excel/CSV | maatwebsite/excel | – |
| Telefónne čísla | propaganistas/laravel-phone | – |
| Modulárnosť | nwidart/laravel-modules | – |
| CAPTCHA | Cloudflare Turnstile | – |

---

## Modulárna architektúra

Projekt je rozdelený do 12 modulov (`Modules/`), každý zodpovedný za svoju doménu:

```
nti-backend/
├── app/
│   └── Services/                   # Zdieľané servisné triedy
│       ├── NotificationService.php
│       └── ApplicationWorkflowService.php
├── Modules/
│   ├── Applications/               # Žiadosti, dokumenty, stavový automat
│   ├── AuditCompliance/            # GDPR reporty, systémové udalosti
│   ├── Content/                    # CMS obsah, kontaktné formuláre
│   ├── Evaluation/                 # Komisie, hodnotenia, rozhodnutia
│   ├── IdentityAccess/             # Auth, registrácia, roly, súhlasy
│   ├── Mentorship/                 # Mentori, míľniky, konzultácie
│   ├── Notifications/              # In-app notifikácie, email šablóny
│   ├── Organizations/              # Organizácie, sektory, pozvánky
│   ├── Programs/                   # Grantové výzvy, formuláre, kritériá
│   ├── Reporting/                  # KPI, výstupy, exporty, dashboardy
│   ├── Students/                   # Profily študentov, akademické záznamy
│   └── Teams/                      # Tímy, členovia, roly, pozvánky
└── routes/
    └── api.php                     # Centrálny API route file
```

---

## Databázová schéma

### Kľúčové tabuľky

| Tabuľka | Modul | Poznámka |
|---------|-------|----------|
| `users` | IdentityAccess | SoftDeletes, MustVerifyEmail |
| `application` | Applications | `timestamps=false`, SoftDeletes |
| `call` | Programs | Grantové výzvy |
| `team` | Teams | `fillable=['name']` |
| `team_members` | Teams | Pivot, `timestamps=false` |
| `commission` | Evaluation | `fillable=['name']` |
| `commission_member` | Evaluation | `timestamps=false`, má `call_id` |
| `evaluation` | Evaluation | Hodnotenie žiadosti |
| `mentorship` | Mentorship | FK `mentor_user_id` (nie `mentor_id`) |
| `project_milestones` | Mentorship | Tabuľka sa volá `project_milestones` |
| `organization` | Organizations | E164PhoneNumberCast na `phone`, pole `ico` |
| `student` | Students | Profil študenta |
| `notifications` | Notifications | In-app notifikácie (nie Laravel DB notif) |
| `email_template` | Notifications | Email šablóny |
| `gdpr_report` | AuditCompliance | SoftDeletes, expiruje za 15 min |
| `system_event` | AuditCompliance | `timestamps=false` (len `created_at`) |
| `project_kpi` | Reporting | KPI metriky |
| `project_output` | Reporting | Výstupy projektov |
| `export_request` | Reporting | Asynchrónne exporty |
| `form_schema` | Programs | Verzie formulárov |
| `form_field` | Programs | Polia: `name`, `type`, `config` |
| `criterion` | Programs | `fillable=['code']`, name je computed |
| `status_of_application` | Applications | Číselník stavov žiadostí |
| `user_consent` | IdentityAccess | `consent_id`, `granted` |

### Dôležité pivot tabuľky

| Pivot | Prepája |
|-------|---------|
| `document_has_application` | Document ↔ Application |
| `document_has_milestone` | Document ↔ Milestone |
| `document_has_project_output` | Document ↔ ProjectOutput |
| `call_commission_setup` | Call ↔ Commission |
| `call_has_criterion` | Call ↔ Criterion (weight, is_academic_signal) |
| `sector_has_organization` | Sector ↔ Organization |
| `student_has_academic_flags` | Student ↔ AcademicFlag (FK: `academic_flags_id`) |

---

## Autentifikácia a autorizácia

### Laravel Sanctum

API používa **Bearer token autentifikáciu** cez Laravel Sanctum:

```http
Authorization: Bearer <token>
```

Token je vrátený pri prihlásení (`POST /api/auth/login`) a registrácii (`POST /api/auth/register`).

### Middleware skupiny

```php
// Vyžaduje iba autentifikáciu
Route::middleware(['auth:sanctum'])

// Vyžaduje autentifikáciu + overený email
Route::middleware(['auth:sanctum', 'verified'])
```

### Role systém

Implementované cez `spatie/laravel-permission`. Pomocné metódy na `User` modeli:

| Metóda | Názov roly v DB |
|--------|----------------|
| `$user->isAdmin()` | `nti_admin` |
| `$user->isSuperAdmin()` | `nti_superadmin` |
| `$user->isCommissionChair()` | `predseda_komisie` |
| `$user->isCMSEditor()` | `cms_editor` |

> Názvy rolí sú presne takto uložené v databáze (mix slovenčiny a angličtiny).

### Email verifikácia

Model `User` implementuje `MustVerifyEmail`. Vlastná trieda `CustomVerifyEmail` číta jazyk z cookie `i18n_redirected` a odosiela localizovaný verifikačný email.

---

## Stavový automat žiadostí

### ApplicationStateMachine

**Súbor:** `Modules/Applications/app/StateMachines/ApplicationStateMachine.php`

Riadi povolené prechody medzi stavmi žiadosti. Stav je uložený ako ID v stĺpci `active_status` (FK na `status_of_application.id`).

### Stavy žiadostí

| Konštanta | Hodnota |
|-----------|---------|
| `STATE_DRAFT` | `'Draft'` |
| `STATE_SUBMITTED` | `'Podané'` |
| `STATE_IN_EVALUATION` | `'V hodnotení'` |
| `STATE_SUPPLEMENT_REQUESTED` | `'Vyžiadané doplnenie'` |
| `STATE_APPROVED` | `'Schválené'` |
| `STATE_REJECTED` | `'Zamietnuté'` |
| `STATE_PAUSED` | `'Pozastavené'` |
| `STATE_ONBOARDING` | `'Onboarding'` |
| `STATE_ACTIVE_PROJECT` | `'Aktívny projekt'` |
| `STATE_COMPLETED` | `'Ukončené'` |

### Graf prechody

```
Draft ──────────────────────────────► Podané
                                          │
                        ┌─────────────────┤
                        ▼                 ▼
              Vyžiadané doplnenie    V hodnotení
                        │            │     │
                        └────────────┘     │
                                     │     │
                              Schválené  Zamietnuté
                                     │
                                Onboarding
                                     │
                             Aktívny projekt
                               │          │
                          Pozastavené  Ukončené
                               │
                           Aktívny projekt / Ukončené
```

---

## Asynchrónne spracovanie

### Queue Jobs

| Job | Modul | Popis |
|-----|-------|-------|
| `ProcessGdprExport` | AuditCompliance | GDPR export (PDF/XLSX/CSV), 3 pokusy, 120s timeout |
| `GenerateExportRequestFileJob` | Reporting | Export žiadostí (excel/pdf) |

### ProcessGdprExport – kľúčové detaily

- Ukladá súbory na disk `'local'` do `gdpr_reports/`
- Expirácia: `now()->addMinutes(15)` (15 minút)
- Security classification: `'confidential'`
- Formáty: PDF (DomPDF), XLSX (Maatwebsite\Excel), CSV (Maatwebsite\Excel)

### GenerateExportRequestFileJob – kľúčové detaily

- Cesta k súboru: `exports/{user_id}/{export_key}-{id}.{format}`
- Stavy ExportRequest: `pending → processing → completed/failed`

---

## Servisná vrstva

Zdieľané servisné triedy sú v `app/Services/` (nie v moduloch):

### App\Services\NotificationService

Vytvára in-app notifikácie (`Notifications` model) a odosiela emaily.

```php
$notificationService->notifyAdminsApplicationSubmitted($application);
$notificationService->notifyAdminsEvaluationSubmitted($evaluation);
$notificationService->notifyEvaluatorAssigned($evaluation);
$notificationService->notifyTeamApplicationStatusChange($application, $statusName, $note, $actor);
```

### App\Services\ApplicationWorkflowService

Obaľuje `ApplicationStateMachine` a koordinuje notifikácie pri zmenách stavov.

```php
$workflowService->changeStatus($application, statusId: null, statusName: 'V hodnotení', note: '...', changedBy: $user);
$workflowService->submitApplication($application, $user, 'Poznámka');
$workflowService->requestSupplement($application, 'Doplniť prílohu B', $user);
```

---

## Závislosť medzi modulmi

```
IdentityAccess (User, Auth)
    ├── Organizations (org membership)
    ├── Students (student profile)
    └── Teams (team membership)

Programs (Call, FormSchema)
    ├── Applications (call_id, form_schema_id)
    └── Evaluation (call_commission_setup)

Applications (Application)
    ├── Evaluation (commission_member → application)
    ├── Mentorship (application_id)
    ├── Reporting (ProjectKpi, ProjectOutput)
    └── AuditCompliance (GdprReport.attachment → Document)

Mentorship (Milestone)
    └── Programs (call_id)

App\Services (zdieľané)
    ├── NotificationService → Notifications module model
    └── ApplicationWorkflowService → Applications module
```

---

## Inštalácia a konfigurácia

### Požiadavky

- PHP 8.2+
- Composer
- PostgreSQL 15+
- Redis 7+
- Node.js (pre asset kompilátory, ak potrebné)

### Inštalácia závislostí

```bash
composer install
```

### Konfigurácia prostredia

```bash
cp .env.example .env
php artisan key:generate
```

### Kľúčové .env premenné

```env
APP_NAME=NTI
APP_ENV=local
APP_KEY=

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nti
DB_USERNAME=...
DB_PASSWORD=...

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis

# Cloudflare Turnstile
TURNSTILE_SECRET=...

# Mail
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
```

### Migrácie a seedy

```bash
php artisan migrate
php artisan db:seed
```

### Queue Worker

```bash
php artisan queue:work redis --queue=default
```

---

## Prehľad API endpointov

### Verejné endpointy (bez autentifikácie)

| Prefix | Popis |
|--------|-------|
| `POST /api/auth/login` | Prihlásenie |
| `POST /api/auth/register` | Registrácia |
| `POST /api/auth/forgot-password` | Zabudnuté heslo |
| `POST /api/auth/reset-password` | Reset hesla |
| `GET /api/v1/calls` | Zoznam grantových výziev |
| `GET /api/study-fields` | Odbory štúdia |
| `GET /api/study-programs` | Programy štúdia |
| `GET /api/study-years` | Ročníky štúdia |
| `GET /api/sectors` | Sektory organizácií |
| `GET /api/content/*` | CMS obsah |
| `POST /api/contact` | Kontaktný formulár |

### Chránené endpointy (auth:sanctum)

| Prefix | Modul | Popis |
|--------|-------|-------|
| `/api/applications*` | Applications | Správa žiadostí |
| `/api/documents*` | Applications | Dokumenty |
| `/api/teams*` | Teams | Tímy a členovia |
| `/api/organizations*` | Organizations | Organizácie |
| `/api/students*` | Students | Profily študentov |
| `/api/mentorships*` | Mentorship | Mentorstvo |
| `/api/milestones*` | Mentorship | Míľniky |
| `/api/notifications*` | Notifications | Notifikácie |
| `/api/email-templates*` | Notifications | Email šablóny |
| `/api/project-kpis*` | Reporting | KPI metriky |
| `/api/project-outputs*` | Reporting | Výstupy projektov |
| `/api/exports*` | Reporting | Exporty |
| `/api/gdpr-reports*` | AuditCompliance | GDPR reporty |
| `/api/v1/admin/*` | Rôzne | Admin operácie |
| `/api/evaluations*` | Evaluation | Hodnotenia |
| `/api/v1/admin/commissions*` | Evaluation | Komisie |

---

## Kľúčové konvencie

### Tabuľky vs. Model názvy

Viaceré tabuľky majú nezvyčajné názvy – nesúhlasia s Laravel konvenciou množného čísla:

| Model | Tabuľka |
|-------|---------|
| `Application` | `application` (nie `applications`) |
| `Call` | `call` (nie `calls`) |
| `Team` | `team` (nie `teams`) |
| `Commission` | `commission` (nie `commissions`) |
| `CommissionMember` | `commission_member` |
| `Mentorship` | `mentorship` |
| `Milestone` | `project_milestones` (!) |
| `Organization` | `organization` |
| `Student` | `student` |
| `FormSchema` | `form_schema` |
| `FormField` | `form_field` |
| `Criterion` | `criterion` |

### Timestamps

Niektoré modely nemajú štandardné Laravel timestamps:

| Model | timestamps |
|-------|-----------|
| `Application` | `false` – má vlastné `last_update` a `submitted_at` |
| `CommissionMember` | `false` |
| `TeamMember` (Pivot) | `false` |
| `SystemEvent` | `false` – má len `created_at` v fillable |

### Slovenské hodnoty v číselníkoch

Viaceré enum/číselníkové hodnoty sú v slovenčine:

- Stavy žiadostí: `'Podané'`, `'V hodnotení'`, `'Vyžiadané doplnenie'`, atď.
- Tímové roly: `'Vedúci tímu'`, `'Člen tímu'`
- Roly používateľov: `'nti_admin'`, `'predseda_komisie'`

### FormRequest triedy

**Iba modul Reporting** obsahuje FormRequest triedy:
- `StoreProjectKpiRequest`
- `StoreProjectOutputRequest`

Ostatné moduly nevyužívajú FormRequest – validácia prebieha priamo v kontroléroch cez `$request->validate(...)`.

### GDPR expirácia

GDPR reporty expirujú za **15 minút** po vygenerovaní (nie dni). Súbory sú ukladané na disk `'local'`.

### Email šablóny

Premenné v emailových šablónach majú `$` prefix:
```
{{ $userName }}      ← správne
{{ userName }}       ← nesprávne
```

---

## Moduly – prehľad dokumentácie

| Modul | README |
|-------|--------|
| Applications | [Modules/Applications/README.md](Modules/Applications/README.md) |
| AuditCompliance | [Modules/AuditCompliance/README.md](Modules/AuditCompliance/README.md) |
| Content | [Modules/Content/README.md](Modules/Content/README.md) |
| Evaluation | [Modules/Evaluation/README.md](Modules/Evaluation/README.md) |
| IdentityAccess | [Modules/IdentityAccess/README.md](Modules/IdentityAccess/README.md) |
| Mentorship | [Modules/Mentorship/README.md](Modules/Mentorship/README.md) |
| Notifications | [Modules/Notifications/README.md](Modules/Notifications/README.md) |
| Organizations | [Modules/Organizations/README.md](Modules/Organizations/README.md) |
| Programs | [Modules/Programs/README.md](Modules/Programs/README.md) |
| Reporting | [Modules/Reporting/README.md](Modules/Reporting/README.md) |
| Students | [Modules/Students/README.md](Modules/Students/README.md) |
| Teams | [Modules/Teams/README.md](Modules/Teams/README.md) |

---

*NTI Backend – Laravel 12 | Modulárna architektúra*
