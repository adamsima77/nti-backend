# Modul Applications – Dokumentácia

> Správa žiadostí, dokumentov, stavového automatu a workflow prechodu žiadostí v NTI platforme.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Stavový automat (ApplicationStateMachine)](#stavový-automat)
5. [Workflow servis](#workflow-servis)
6. [Kontroléry a endpoints](#kontroléry-a-endpoints)
7. [Dokumenty](#dokumenty)
8. [Exporty](#exporty)
9. [Udalosti a notifikácie](#udalosti-a-notifikácie)
10. [Policies](#policies)
11. [Integrácie](#integrácie)
12. [Príklady použitia](#príklady-použitia)
13. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Applications** zabezpečuje celý životný cyklus projektových žiadostí – od vytvorenia draftu cez podanie, hodnotenie, schválenie až po ukončenie projektu. Žiadosti sú viazané na výzvy (`call`) a tímy (`team`). Workflow je riadený stavovým automatom `ApplicationStateMachine`.

### Zodpovednosti modulu

- Vytváranie, editácia a ukladanie žiadostí ako draftu
- Podanie (submit) žiadosti s validáciou povinných polí
- Správa odpovedí formulára (`ApplicationAnswer`)
- Nahrávanie a verzionávanie dokumentov
- Prechody stavov cez `ApplicationStateMachine`
- Export žiadostí do PDF a XLSX/CSV formátov
- História zmien stavov (`ApplicationStatusHistory`)

---

## Adresárová štruktúra

```
Modules/Applications/
├── app/
│   ├── Events/
│   │   └── ApplicationStatusChanged.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ApplicationController.php       # Hlavný kontrolér
│   │   │   ├── ApplicationsController.php      # Legacy wrapper
│   │   │   ├── DocumentController.php          # Nahrávanie/sťahovanie dokumentov
│   │   │   └── StatusOfApplicationController.php
│   │   ├── Middleware/
│   │   │   └── CheckApplicationOwnership.php
│   │   └── Resources/
│   │       └── ApplicationResource.php
│   ├── Models/
│   │   ├── Application.php
│   │   ├── ApplicationAnswer.php
│   │   ├── ApplicationStatusHistory.php
│   │   ├── Document.php
│   │   ├── DocumentVersion.php
│   │   ├── SecurityClassification.php
│   │   ├── StatusOfApplication.php
│   │   └── TypeOfApplication.php
│   ├── Observers/
│   │   └── ApplicationObserver.php
│   ├── Policies/
│   │   ├── ApplicationsPolicy.php
│   │   ├── DocumentPolicy.php
│   │   └── StatusOfApplicationPolicy.php
│   ├── Providers/
│   │   ├── ApplicationsServiceProvider.php
│   │   └── EventServiceProvider.php
│   └── StateMachines/
│       └── ApplicationStateMachine.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/views/
│   └── pdf/application-details.blade.php
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Application

**Tabuľka:** `application`

```
application
├── id                  (bigIncrements)
├── reference           (string, nullable, unique)
├── submitted_at        (timestamp, nullable)
├── last_update         (timestamp)
├── call_id             (unsignedBigInteger → call.id)
├── team_id             (unsignedBigInteger → team.id)
├── created_by          (unsignedBigInteger → users.id)
├── active_status       (unsignedBigInteger → status_of_application.id)
├── category_id         (unsignedBigInteger → categories.id, nullable)
├── form_data           (json, nullable)
├── form_schema_id      (unsignedBigInteger → form_schema.id, nullable)
└── deleted_at          (softDeletes)
```

> **Poznámka:** `timestamps = false` – tabuľka nemá automatické `created_at`/`updated_at`. Čas sa sleduje cez `last_update`.

**Model (`Application.php`):**

```php
class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'application';
    public $timestamps = false;

    protected $fillable = [
        'submitted_at', 'last_update', 'call_id', 'team_id',
        'created_by', 'active_status', 'category_id',
        'form_data', 'form_schema_id', 'reference'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'last_update'  => 'datetime',
        'form_data'    => 'array',
    ];

    protected $appends = ['academic_flag'];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `status()` | `BelongsTo` | `StatusOfApplication` cez `active_status` |
| `call()` | `BelongsTo` | `Call` cez `call_id` |
| `team()` | `BelongsTo` | `Team` cez `team_id` |
| `creator()` | `BelongsTo` | `User` cez `created_by` |
| `formSchema()` | `BelongsTo` | `FormSchema` cez `form_schema_id` |
| `category()` | `BelongsTo` | `Category` cez `category_id` |
| `answers()` | `HasMany` | `ApplicationAnswer` |
| `documents()` | `BelongsToMany` | `Document` cez `document_has_application` |
| `statusHistory()` | `HasMany` | `ApplicationStatusHistory` |
| `milestones()` | `HasMany` | `Milestone` cez `call_id` |
| `mentorships()` | `HasMany` | `Mentorship` |
| `kpis()` | `HasMany` | `ProjectKpi` |
| `outputs()` | `HasMany` | `ProjectOutput` |

**Computed attribute `academic_flag`:**

Vypočítaný atribút, ktorý indikuje, či všetci členovia tímu majú akademické vlajky. Vracia `true` ak majú všetci, `false` ak niekto nemá, `null` ak niektorý člen nemá profil študenta.

---

### Document

**Tabuľka:** `document`

```
document
├── id
├── owner_id                    (FK → users.id)
├── security_classification_id  (FK → security_classification.id)
└── timestamps
```

### DocumentVersion

**Tabuľka:** `document_version`

```
document_version
├── id
├── document_id  (FK → document.id, CASCADE DELETE)
├── file_name    (string)
├── file_path    (string)
└── timestamps
```

### document_has_application (pivot)

```
document_has_application
├── document_id            (FK)
├── application_id         (FK)
└── type_of_application_id (FK)
```

### ApplicationStatusHistory

**Tabuľka:** `application_status_history`

```
application_status_history
├── id
├── status_of_application_id  (FK → status_of_application.id)
├── application_id            (FK → application.id)
├── note                      (text, nullable)
├── changed_by                (unsignedBigInteger → users.id, nullable)
└── timestamps
```

### StatusOfApplication

**Tabuľka:** `status_of_application`

Číselník stavov žiadosti. Záznamy sú seedované:

| Stav (name) | Popis |
|-------------|-------|
| `Draft` | Rozpracovaná žiadosť |
| `Podané` | Podaná žiadosť |
| `V hodnotení` | V procese hodnotenia |
| `Vyžiadané doplnenie` | Potrebné doplniť |
| `Schválené` | Schválená |
| `Zamietnuté` | Zamietnutá |
| `Pozastavené` | Pozastavená |
| `Onboarding` | Vo fáze onboardingu |
| `Aktívny projekt` | Aktívny projekt |
| `Ukončené` | Dokončený projekt |

### ApplicationAnswer

**Tabuľka:** `application_answer`

```
application_answer
├── id
├── application_id  (FK → application.id)
└── answer          (json)
```

Odpovede sú uložené ako JSON pole. Odkaz na `form_schema_id` je na úrovni `Application`.

### SecurityClassification

**Tabuľka:** `security_classification`

Číselník klasifikácií bezpečnosti dokumentov (napr. `confidential`, `public`).

---

## Stavový automat

### ApplicationStateMachine

**Súbor:** `app/StateMachines/ApplicationStateMachine.php`

Trieda riadi povolené prechody medzi stavmi žiadosti. Stav je uložený ako relácia `status()` (BelongsTo na `StatusOfApplication`).

### Konštanty stavov

```php
const STATE_DRAFT                = 'Draft';
const STATE_SUBMITTED            = 'Podané';
const STATE_IN_EVALUATION        = 'V hodnotení';
const STATE_SUPPLEMENT_REQUESTED = 'Vyžiadané doplnenie';
const STATE_APPROVED             = 'Schválené';
const STATE_REJECTED             = 'Zamietnuté';
const STATE_PAUSED               = 'Pozastavené';
const STATE_ONBOARDING           = 'Onboarding';
const STATE_ACTIVE_PROJECT       = 'Aktívny projekt';
const STATE_COMPLETED            = 'Ukončené';
```

### Povolené prechody (TRANSITIONS)

```
Draft
  └─► Podané

Podané
  ├─► V hodnotení
  └─► Vyžiadané doplnenie

Vyžiadané doplnenie
  └─► Podané

V hodnotení
  ├─► Schválené
  ├─► Zamietnuté
  └─► Vyžiadané doplnenie

Schválené
  └─► Onboarding

Onboarding
  └─► Aktívny projekt

Aktívny projekt
  ├─► Pozastavené
  └─► Ukončené

Pozastavené
  ├─► Aktívny projekt
  └─► Ukončené

Zamietnuté  → (žiadne ďalšie prechody)
Ukončené    → (žiadne ďalšie prechody)
```

### Povinné polia pred prechodom (REQUIRED_FIELDS)

| Cieľový stav | Podmienka |
|--------------|-----------|
| `Podané` | `team_id`, `call_id` musia byť vyplnené |
| `V hodnotení` | Musí existovať záznam v `evaluation` pre danú žiadosť |
| `Aktívny projekt` | Musí existovať aspoň jeden mentorship |

### Kľúčové metódy

```php
// Vytvorenie inštancie
$sm = new ApplicationStateMachine($application, $actorUser);

// Overenie aktuálneho stavu
$sm->currentState(); // napr. 'Draft'

// Overenie, či je prechod možný
$sm->canTransitionTo('Podané'); // bool

// Zoznam chýbajúcich polí pred prechodom
$sm->missingFields('V hodnotení'); // ['commission_id']

// Vykonanie prechodu (atómová transakcia)
$sm->transitionTo('Podané', 'Poznámka k podaniu');

// Dostupné prechody z aktuálneho stavu
$sm->availableTransitions(); // ['Podané']
```

### Transakcia pri prechode

`transitionTo()` vykoná v rámci DB transakcie:
1. Aktualizuje `active_status` a `last_update` na žiadosti
2. Pri prechode do `Podané` nastaví aj `submitted_at = now()`
3. Vytvorí záznam v `application_status_history`

---

## Workflow servis

### App\Services\ApplicationWorkflowService

Servis obaľuje `ApplicationStateMachine` a koordinuje notifikácie. Injektovaný cez DI.

```php
// Zmena stavu podľa ID alebo mena stavu
$workflowService->changeStatus(
    application: $application,
    statusId: null,
    statusName: 'V hodnotení',
    note: 'Priradená komisia č. 3',
    changedBy: $adminUser,
);

// Podanie žiadosti (nastaví submitted_at, notifikuje adminov)
$workflowService->submitApplication($application, $user, 'Podáva tím InnoX');

// Vyžiadanie doplnenia
$workflowService->requestSupplement($application, 'Chýba príloha B', $user);
```

Po zmene stavu `submitApplication()` automaticky volá `NotificationService::notifyAdminsApplicationSubmitted()`.

---

## Kontroléry a endpoints

### ApplicationController

**Súbor:** `app/Http/Controllers/ApplicationController.php`

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam žiadostí pre prihlásit. používateľa |
| `show($id)` | Detail žiadosti |
| `store()` | Vytvorenie novej žiadosti |
| `update($id)` | Aktualizácia žiadosti |
| `submit($id)` | Podanie žiadosti (prechod Draft → Podané) |
| `submitApplication()` | Alternatívny submit endpoint |
| `storeDraft()` | Uloženie draftu |
| `findForCall()` | Nájdenie žiadosti pre konkrétnu výzvu a tím |
| `getApplicationAnswer($application)` | Odpovede formulára |
| `updateStatus($id)` | Zmena stavu (team leader) |
| `fetchForAdmin()` | Zoznam žiadostí pre admina |
| `updateStateAdmin($application)` | Admin zmena stavu cez ApplicationStateMachine |
| `addCommittee($application, $committee)` | Priradenie komisie |
| `removeCommittee($application)` | Odobratie komisie |
| `deleteMentor($application, $mentorship)` | Zmazanie mentora |

**`updateStateAdmin` – kľúčová logika:**

```php
public function updateStateAdmin(Request $request, Application $application)
{
    $this->authorize('update', $application);

    $request->validate([
        'state_id' => ['required', 'integer', 'exists:status_of_application,id'],
    ]);

    $targetStatusModel = StatusOfApplication::findOrFail($request->state_id);
    $stateMachine      = new ApplicationStateMachine($application, $request->user());

    if (!$stateMachine->canTransitionTo($targetStatusModel->name)) {
        return response()->json([
            'message' => "Prechod do stavu '{$targetStatusModel->name}' nie je povolený!"
        ], 403);
    }

    DB::transaction(function () use ($stateMachine, $targetStatusModel, $request) {
        $stateMachine->transitionTo($targetStatusModel->name, $request->input('note'));
    });

    app(NotificationService::class)->notifyTeamApplicationStatusChange(
        $application->fresh(['status', 'team.members', 'creator', 'call']),
        $targetStatusModel->name,
        $request->input('note'),
        $request->user(),
    );

    return response()->json(['message' => 'Stav úspešne zmenený!']);
}
```

---

## API Routes

**Súbor:** `routes/api.php`

Všetky routes vyžadujú autentifikáciu (`auth:sanctum`) a overený email (`verified`).

### Exporty (throttle: 25/min)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/applications/export/{format?}` | Export žiadostí (xlsx/csv/pdf) |
| `GET` | `/applications/{id}/pdf` | PDF detail žiadosti |

### Dokumenty a žiadosti (throttle: application)

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/documents` | Nahranie dokumentu |
| `GET` | `/documents/{document}` | Detail dokumentu |
| `GET` | `/documents/{document}/download` | Stiahnutie dokumentu |
| `GET` | `/applications` | Zoznam mojich žiadostí |
| `GET` | `/applications/find` | Nájsť žiadosť pre call a tím |
| `GET` | `/applications/{id}` | Detail žiadosti |
| `POST` | `/applications` | Vytvorenie žiadosti |
| `PUT` | `/applications/{id}` | Aktualizácia žiadosti |
| `PATCH` | `/applications/{id}` | Čiastočná aktualizácia |
| `POST` | `/applications/{id}/submit` | Podanie žiadosti |
| `POST` | `/submit-application` | Alternatívny submit |
| `POST` | `/applications/draft` | Uloženie draftu |
| `GET` | `/application-answer/{application}` | Odpovede formulára |
| `GET` | `/status-of-applications` | Zoznam stavov |
| `PATCH` | `/applications/{id}/status` | Zmena stavu |

### Admin endpoints

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/admin/applications` | Všetky žiadosti pre admina |
| `GET` | `/admin-app-statuses` | Admin stavy |
| `GET` | `/get-status-admin` | Stavy okrem Draft |
| `POST` | `/change-app-state/{application}/admin` | Admin zmena stavu |
| `DELETE` | `/remove-committee/{application}` | Odobratie komisie |
| `POST` | `/add-committee/{application}/committee/{committee}` | Priradenie komisie |
| `DELETE` | `/admin/applications/{application}/mentorships/{mentorship}` | Zmazanie mentora |

---

## Dokumenty

### DocumentController

Spravuje nahrávanie a sťahovanie súborov prepojených so žiadosťami.

```
store()    → POST /documents
show()     → GET  /documents/{document}
download() → GET  /documents/{document}/download
```

Dokumenty sú ukladané na privátnom disku (Laravel Storage). Prístup je kontrolovaný cez `DocumentPolicy`.

### Verzionávanie

Každý `Document` má historiu cez `DocumentVersion`. Nová verzia sa vytvára pridaním záznamu do `document_version` bez mazania starých verzií.

### SecurityClassification

Každý dokument má bezpečnostnú klasifikáciu (`security_classification_id`). Táto klasifikácia sa využíva pri GDPR exportoch – GDPR reporty sú automaticky klasifikované ako `confidential`.

---

## Exporty

Exporty žiadostí obsluhuje `ExportController` z modulu Reporting:

- **`GET /applications/export/{format?}`** – XLSX, CSV alebo PDF export zoznamu žiadostí
- **`GET /applications/{id}/pdf`** – PDF s detailom konkrétnej žiadosti

PDF šablóna: `resources/views/pdf/application-details.blade.php`

Export prebieha cez `QueuedExportService` (asynchrónne, ukladá sa ako `ExportRequest`).

---

## Udalosti a notifikácie

### ApplicationStatusChanged

**Súbor:** `app/Events/ApplicationStatusChanged.php`

Udalosť je spúšťaná pri každej zmene stavu žiadosti.

### NotificationService (App\Services)

Servis `App\Services\NotificationService` zabezpečuje notifikácie pre rôzne scenáre:

| Metóda | Popis |
|--------|-------|
| `notifyAdminsApplicationSubmitted($application)` | Notifikuje adminov pri podaní žiadosti |
| `notifyAdminsEvaluationSubmitted($evaluation)` | Notifikuje adminov pri podaní hodnotenia |
| `notifyEvaluatorAssigned($evaluation)` | Notifikuje hodnotiteľa pri pridelení |
| `notifyTeamApplicationStatusChange(...)` | Notifikuje tím pri zmene stavu |

Notifikácie sú ukladané do tabuľky `notifications` cez model `Notifications` z modulu Notifications.

### ApplicationObserver

**Súbor:** `app/Observers/ApplicationObserver.php`

Observer reaguje na udalosti modelu (created, updated, deleted) pre logovanie a audit cez modul AuditCompliance.

---

## Policies

### ApplicationsPolicy

**Súbor:** `app/Policies/ApplicationsPolicy.php`

Kontroluje oprávnenia pre operácie so žiadosťami. Metódy: `viewAny`, `create`, `view`, `update`, `delete`, `approve`, `reject`, `changeStatus`, `export`.

### DocumentPolicy

**Súbor:** `app/Policies/DocumentPolicy.php`

Prístup k dokumentom majú: vlastník dokumentu, členovia tímu žiadosti, hodnotitelia. Metódy: `view`, `create`, `update`, `delete`.

### StatusOfApplicationPolicy

**Súbor:** `app/Policies/StatusOfApplicationPolicy.php`

Zoznam stavov môžu vidieť len admini a super admini.

---

## Integrácie

### Programs
- `Application.call_id` → `call.id`
- Odpovede sú uložené v `form_data` (JSON) alebo cez `ApplicationAnswer` s odkazom na `form_schema_id`
- Pri podaní sa overuje stav výzvy

### Teams
- `Application.team_id` → `team.id`
- Prístupové kontroly vychádzajú z členstva v tíme

### Evaluation
- Relácia `Application.evaluations()` → `HasMany(Evaluation)`
- Pred prechodom do `V hodnotení` musí existovať `CommissionMember` pre danú žiadosť

### Mentorship
- Relácia `Application.mentorships()` → `HasMany(Mentorship)`
- Pred prechodom do `Aktívny projekt` musí existovať mentorstvo

### Reporting
- `Application.kpis()` → `HasMany(ProjectKpi)`
- `Application.outputs()` → `HasMany(ProjectOutput)`

### AuditCompliance
- `ApplicationObserver` loguje zmeny do `system_events`
- GDPR export zahŕňa žiadosti používateľa

---

## Príklady použitia

### Vytvorenie a podanie žiadosti

```http
# 1. Vytvorenie žiadosti
POST /api/applications
Authorization: Bearer <token>
Content-Type: application/json

{
  "call_id": 5,
  "team_id": 3
}
```

```http
# 2. Podanie žiadosti
POST /api/applications/42/submit
Authorization: Bearer <token>
```

### Admin zmena stavu žiadosti

```http
POST /api/change-app-state/42/admin
Authorization: Bearer <token>
Content-Type: application/json

{
  "state_id": 3,
  "note": "Žiadosť spĺňa podmienky výzvy, posúvaná do hodnotenia."
}
```

### Stiahnutie PDF žiadosti

```http
GET /api/applications/42/pdf
Authorization: Bearer <token>
```

### Export žiadostí (XLSX)

```http
GET /api/applications/export/xlsx
Authorization: Bearer <token>
```

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Žiadosť načítaná / stav zmenený |
| `201` | Žiadosť vytvorená |
| `403` | Prechod stavu nie je povolený / nedostatočné oprávnenia |
| `404` | Žiadosť / dokument neexistuje |
| `410` | Verifikácia emailu je povinná (`verified` middleware) |
| `422` | Validačná chyba (napr. `state_id` neexistuje) |
| `422` | Žiadosť sa už nachádza v požadovanom stave |

### Typické chybové JSON odpovede

```json
// Nepovolený prechod stavu
{
  "message": "Prechod do stavu 'Schválené' nie je povolený!"
}

// Chýbajúce povinné polia
{
  "message": "Chýbajú povinné dáta pre prechod do stavu \"V hodnotení\": commission_id"
}
```

---

## Konfigurácia throttle

Modul používa pomenovaný throttle `application` definovaný v `App\Providers\RouteServiceProvider`:
- Exporty: 25 req/min (separátna skupina)
- Štandardné operácie: podľa `throttle:application`

---

*Modul Applications – NTI Backend | Laravel 12*
