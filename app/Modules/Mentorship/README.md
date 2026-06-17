# Modul Mentorship – Dokumentácia

> Prideľovanie mentorov, správa míľnikov, konzultačné sessions a schvaľovanie na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Kontroléry a logika](#kontroléry-a-logika)
5. [API Routes](#api-routes)
6. [Integrácie](#integrácie)
7. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Mentorship** spravuje prideľovanie mentorov k schváleným projektom a evidenciu:

- Vytvorenie mentorského vzťahu (Mentorship) medzi mentorom a žiadosťou
- Konzultačné stretnutia (MentorshipSession) – plánované, prebiehajúce, dokončené
- Spätná väzba od mentora
- Míľniky projektu (Milestone) viazané na výzvu
- Nahrávanie dokumentov k míľnikom

---

## Adresárová štruktúra

```
Modules/Mentorship/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── MentorshipController.php
│   │       ├── MilestoneController.php
│   │       └── MilestoneDocumentController.php
│   ├── Models/
│   │   ├── Mentorship.php
│   │   ├── MentorshipSession.php
│   │   └── Milestone.php
│   ├── Policies/
│   │   └── MentorshipPolicy.php
│   └── Providers/
│       └── MentorshipServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Mentorship

**Tabuľka:** `mentorship`

```
mentorship
├── id
├── mentor_user_id  (FK → users.id)   – ID mentora (používateľa)
├── application_id  (FK → application.id)
└── timestamps
```

> **Kriticky dôležité:** Stĺpec sa volá `mentor_user_id` – NIE `mentor_id`.

**Model:**

```php
class Mentorship extends Model
{
    protected $table = 'mentorship';

    protected $fillable = [
        'mentor_user_id',   // ← presný názov stĺpca
        'application_id',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `mentor()` | `BelongsTo` | `User` cez `mentor_user_id` |
| `application()` | `BelongsTo` | `Application` |
| `sessions()` | `HasMany` | `MentorshipSession` |

---

### MentorshipSession

**Tabuľka:** `mentorship_session`

```
mentorship_session
├── id
├── mentorship_id  (FK → mentorship.id)
├── title          (string)
├── duration       (integer, v minútach)
├── created_by     (FK → users.id)
├── date           (date)
├── type           (enum: online/offline)
├── meeting_url    (string, nullable)
├── scheduled_at   (timestamp, nullable)
├── agenda         (text, nullable)
├── status         (enum: scheduled/completed/cancelled)
└── timestamps
```

**Model:**

```php
class MentorshipSession extends Model
{
    protected $table = 'mentorship_session';

    protected $fillable = [
        'mentorship_id',
        'title',
        'duration',
        'created_by',
        'date',
        'type',           // 'online' alebo 'offline'
        'meeting_url',
        'scheduled_at',
        'agenda',
        'status',         // 'scheduled', 'completed', 'cancelled'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'date'         => 'date',
    ];
}
```

---

### Milestone

**Tabuľka:** `project_milestones`

> **Kriticky dôležité:** Skutočný názov tabuľky je `project_milestones` – NIE `milestones`.

```
project_milestones
├── id
├── name                 (string)
├── description          (text, nullable)
├── deadline             (date)
├── status               (string)
├── comments             (text, nullable)
├── call_id              (FK → call.id)   – míľnik je viazaný na VÝZVU, nie na žiadosť!
├── start_date           (date, nullable)
├── milestone_status_id  (FK → milestone_status.id)
└── timestamps
```

> **Dôležité:** `Milestone.call_id` odkazuje na tabuľku `call`, nie na `application`. Míľniky sú spoločné pre výzvu, nie pre individuálnu žiadosť.

**Model:**

```php
class Milestone extends Model
{
    protected $table = 'project_milestones';

    protected $fillable = [
        'name', 'description', 'deadline', 'status',
        'comments', 'call_id', 'start_date', 'milestone_status_id',
    ];

    protected $casts = [
        'deadline'   => 'date',
        'start_date' => 'date',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `call()` | `BelongsTo` | `Call` cez `call_id` |
| `documents()` | `BelongsToMany` | `Document` cez `document_has_milestone` |
| `milestoneStatus()` | `BelongsTo` | `MilestoneStatus` |

---

## Kontroléry a logika

### MentorshipController

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam mentorstiev |
| `show($id)` | Detail mentorstva |
| `store()` | Vytvorenie mentorstva (admin) |
| `update($id)` | Aktualizácia mentorstva |
| `destroy($id)` | Zmazanie mentorstva |
| `getProjects()` | Projekty pridelené mentorovi |
| `getConsultations($id)` | Sessions pre dané mentorstvo |
| `createSession($id)` | Vytvorenie novej session |
| `updateSession($id, $sessionId)` | Aktualizácia session |
| `submitFeedback($id)` | Odoslanie spätnej väzby |
| `assignMentor($applicationId)` | Priradenie mentora k žiadosti |

### MilestoneController

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam míľnikov |
| `show($id)` | Detail míľnika |
| `store()` | Vytvorenie míľnika (admin) |
| `update($id)` | Aktualizácia míľnika |
| `destroy($id)` | Zmazanie míľnika |
| `getMilestonesForCall($call)` | Míľniky pre danú výzvu |

### MilestoneDocumentController

Spravuje dokumenty priradené k míľnikom.

| Metóda | Popis |
|--------|-------|
| `upload($milestone)` | Nahranie dokumentu k míľniku |
| `download($milestone, $document)` | Stiahnutie dokumentu míľnika |

---

## API Routes

**Súbor:** `routes/api.php`

Všetky routes vyžadujú autentifikáciu (`auth:sanctum`) a overený email (`verified`).

### Míľniky

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/milestones` | Zoznam míľnikov |
| `GET` | `/api/milestones/{milestone}` | Detail míľnika |
| `POST` | `/api/milestones` | Vytvorenie míľnika |
| `PUT` | `/api/milestones/{milestone}` | Aktualizácia míľnika |
| `DELETE` | `/api/milestones/{milestone}` | Zmazanie míľnika |
| `GET` | `/api/calls/{call}/milestones` | Míľniky výzvy |
| `POST` | `/api/calls/{call}/milestones` | Pridanie míľnika k výzve |

### Dokumenty míľnikov

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/milestones/{milestone}/documents` | Nahranie dokumentu |
| `GET` | `/api/milestones/{milestone}/documents/{document}` | Stiahnutie dokumentu |

### Mentorstvá (prefix `mentor/`)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/mentor/projects` | Projekty mentora |
| `GET` | `/api/mentor/consultations/{mentorship}` | Sessions mentorstva |
| `POST` | `/api/mentor/consultations/{mentorship}` | Nová session |
| `PUT` | `/api/mentor/consultations/{mentorship}/{session}` | Aktualizácia session |
| `POST` | `/api/mentor/feedback/{mentorship}` | Odoslanie spätnej väzby |
| `POST` | `/api/mentor/assignMentor/{application}` | Priradenie mentora |

### Admin

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/mentorships` | Zoznam mentorstiev (admin) |
| `GET` | `/api/mentorships/{mentorship}` | Detail mentorstva |
| `POST` | `/api/mentorships` | Vytvorenie mentorstva |
| `PUT` | `/api/mentorships/{mentorship}` | Aktualizácia |
| `DELETE` | `/api/mentorships/{mentorship}` | Zmazanie |

---

## Integrácie

### Applications
- `Mentorship.application_id` → `application.id`
- Pred prechodom do `Aktívny projekt` musí existovať aspoň jedno mentorstvo
- `Application.mentorships()` → `HasMany(Mentorship)`

### Programs
- `Milestone.call_id` → `call.id`
- `Application.milestones()` je HasMany cez `call_id` (nie `application_id`)

### Applications (Documents)
- Dokumenty míľnikov sú ukladané cez `document_has_milestone` pivot
- Dokumenty sú modelom z Applications modulu

### Organizations
- Program officers schvaľujú míľniky cez endpoint `GET /po/calls/{call}/milestone-approvals`

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Mentorstvo/míľnik načítaný |
| `201` | Mentorstvo/session vytvorená |
| `403` | Nie je mentor/admin |
| `404` | Mentorstvo/míľnik nenájdený |
| `422` | Validačná chyba (napr. neplatný typ session) |

---

*Modul Mentorship – NTI Backend | Laravel 12*
