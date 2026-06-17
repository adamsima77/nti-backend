# Modul Programs – Dokumentácia

> Grantové programy, výzvy (calls), dynamické formuláre a hodnotiace kritériá na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Workflow výzvy (CallWorkflow)](#workflow-výzvy)
5. [Dynamické formuláre](#dynamické-formuláre)
6. [Hodnotiace kritériá](#hodnotiace-kritériá)
7. [API Routes](#api-routes)
8. [Integrácie](#integrácie)
9. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Programs** spravuje grantové výzvy (calls) a ich formuláre. Umožňuje:

- Vytváranie a správu grantových výziev s plnou konfiguráciou
- Správu dynamických formulárov (`FormSchema`, `FormField`)
- Konfiguráciu hodnotiacich kritérií s váhami
- Priradenie komisií k výzvam
- Workflow prechody výzvy (otvorenie, zatvorenie)
- Priradenie program officerov (PO)

---

## Adresárová štruktúra

```
Modules/Programs/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── CallController.php
│   │       ├── FormSchemaController.php
│   │       ├── FormFieldController.php
│   │       └── CriterionController.php
│   ├── Models/
│   │   ├── Call.php
│   │   ├── FormSchema.php
│   │   ├── FormField.php
│   │   └── Criterion.php
│   ├── Policies/
│   │   ├── CallPolicy.php
│   │   └── FormSchemaPolicy.php
│   └── Providers/
│       └── ProgramsServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Call

**Tabuľka:** `call`

```
call
├── id
├── title                    (string)
├── description              (text, nullable)
├── slug                     (string, unique)
├── status                   (string, napr. 'draft', 'open', 'closed')
├── po_user_id               (FK → users.id, nullable) – Program Officer
├── budget_type              (string, nullable)
├── budget_min               (decimal, nullable)
├── budget_max               (decimal, nullable)
├── tech_spec                (text, nullable)
├── tech_tags                (json, nullable) – pole stringov
├── deadline                 (timestamp, nullable)
├── opens_at                 (timestamp, nullable)
├── closes_at                (timestamp, nullable)
├── force_closed             (boolean, default: false)
├── allow_team_formation     (boolean)
├── max_team_size            (integer, nullable)
├── min_team_size            (integer, nullable)
└── timestamps
```

**Model:**

```php
class Call extends Model
{
    protected $table = 'call';

    protected $fillable = [
        'title', 'description', 'slug', 'status', 'po_user_id',
        'budget_type', 'budget_min', 'budget_max',
        'tech_spec', 'tech_tags', 'deadline', 'opens_at', 'closes_at',
        'force_closed', 'allow_team_formation', 'max_team_size', 'min_team_size',
    ];

    protected $casts = [
        'tech_tags'   => 'array',
        'deadline'    => 'datetime',
        'opens_at'    => 'datetime',
        'closes_at'   => 'datetime',
        'force_closed' => 'boolean',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `applications()` | `HasMany` | `Application` |
| `formSchemas()` | `HasMany` | `FormSchema` |
| `po()` | `BelongsTo` | `User` cez `po_user_id` |
| `commission()` | `BelongsToMany` | `Commission` cez `call_commission_setup` |
| `callCriteria()` | `BelongsToMany` | `Criterion` cez `call_has_criterion` |
| `milestones()` | `HasMany` | `Milestone` |

**Pivot stĺpce `call_has_criterion`:**

```php
->withPivot('weight', 'is_academic_signal')
```

---

### FormSchema

**Tabuľka:** `form_schema`

```
form_schema
├── id
├── call_id       (FK → call.id)
├── version       (integer)
├── status        (string, napr. 'draft', 'published')
├── title         (json)        – preložené názvy
├── description   (json)        – preložené popisy
├── sections      (json)        – štruktúra sekcií formulára
├── meta          (json, nullable)
├── published_at  (timestamp, nullable)
└── timestamps
```

**Model:**

```php
class FormSchema extends Model
{
    protected $table = 'form_schema';

    protected $fillable = [
        'call_id', 'version', 'status',
        'title', 'description', 'sections', 'meta', 'published_at',
    ];

    protected $casts = [
        'title'        => 'array',
        'description'  => 'array',
        'sections'     => 'array',
        'meta'         => 'array',
        'published_at' => 'datetime',
    ];
}
```

**Kľúčové statické metódy:**

```php
// Získaj posledný publikovaný formulár pre danú výzvu
FormSchema::publishedLatestForCall(int $callId): ?FormSchema

// Rozlíšenie lokalizovaného titulku (vracia string podľa jazyka)
$schema->resolveTitle(string $lang = 'sk'): string

// Rozlíšenie lokalizovaného popisu
$schema->resolveDescription(string $lang = 'sk'): string
```

---

### FormField

**Tabuľka:** `form_field`

```
form_field
├── id
├── form_schema_id  (FK → form_schema.id)
├── sort_order      (integer)
├── name            (string)   – identifikátor poľa
├── type            (string)   – typ poľa (text, textarea, select, ...)
├── config          (json)     – konfigurácia (validácie, možnosti výberu, ...)
└── timestamps
```

> **Dôležité:** Stĺpce sú `name`, `type`, `config` – NIE `label`, `field_key`, `field_type`.

**Model:**

```php
class FormField extends Model
{
    protected $table = 'form_field';

    protected $fillable = [
        'form_schema_id',
        'sort_order',
        'name',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
```

Pole `config` obsahuje konfiguráciu poľa:

```json
{
  "required": true,
  "placeholder": "Zadajte názov projektu...",
  "max_length": 255,
  "options": []
}
```

---

### Criterion

**Tabuľka:** `criterion`

```
criterion
├── id
├── code          (string, unique)  – identifikačný kód kritéria
└── timestamps
```

> **Dôležité:** Jediné fillable pole je `code`. Názov kritéria je computed `Attribute` z preklady (`translations` tabuľka).

**Model:**

```php
class Criterion extends Model
{
    protected $table = 'criterion';

    protected $fillable = ['code'];  // len 'code' je fillable!

    // 'name' je computed Attribute z prekladov, nie DB stĺpec
    public function getNameAttribute(): string
    {
        return $this->translations()
            ->where('lang', app()->getLocale())
            ->value('name') ?? $this->code;
    }
}
```

**Relácia na výzvy:**

```php
// Kritérium je prepojené s výzvami cez pivot 'call_has_criterion':
$criterion->calls()  // BelongsToMany Call with pivot: weight, is_academic_signal
```

---

## Workflow výzvy

### CallWorkflow

**Endpoint:** `PATCH /api/v1/calls/{call}/workflow`

Umožňuje zmenu stavu výzvy (napr. otvorenie, zatvorenie). Prístup len pre adminov.

Stavy výzvy sú uložené v stĺpci `status` na tabuľke `call`. Príklady stavov: `draft`, `open`, `closed`.

Pole `force_closed` umožňuje adminom manuálne zatvoriť výzvu bez ohľadu na `closes_at`.

---

## Dynamické formuláre

Formulárový systém je trojúrovňový:

```
Call (1)
 └── FormSchema (N)  – verzie formulára
       └── FormField (N)  – jednotlivé polia
```

### Publikovanie formulára

```php
// Statická metóda pre získanie aktívneho formulára výzvy:
$schema = FormSchema::publishedLatestForCall($call->id);
```

Formulár musí mať `status = 'published'` a nenulovú hodnotu `published_at`.

### Sekcie formulára

Formulár je rozdelený do sekcií cez JSON pole `sections` na `FormSchema`:

```json
[
  {
    "id": "basic-info",
    "title": {"sk": "Základné informácie", "en": "Basic Information"},
    "fields": ["project-name", "project-description"]
  }
]
```

---

## Hodnotiace kritériá

Každá výzva môže mať priradené hodnotiacie kritériá cez pivot `call_has_criterion`:

```
call_has_criterion
├── call_id
├── criterion_id
├── weight             (decimal)  – váha kritéria v hodnotení
└── is_academic_signal (boolean)  – či je toto akademický signál
```

### Priradenie kritérií k výzve

```http
POST /api/v1/admin/calls/{call}/criteria
Authorization: Bearer <token>
Content-Type: application/json

{
  "criteria": [
    { "criterion_id": 1, "weight": 30, "is_academic_signal": false },
    { "criterion_id": 2, "weight": 70, "is_academic_signal": true }
  ]
}
```

---

## API Routes

**Súbor:** `routes/api.php`

### Verejné endpointy (throttle: 60/min)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/v1/calls` | Zoznam verejných výziev |
| `GET` | `/api/v1/calls/{call}` | Detail výzvy |
| `GET` | `/api/v1/calls/{call}/form-schema` | Aktívny formulár výzvy |

### Admin endpointy (auth:sanctum + verified)

#### Správa výziev

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/v1/admin/calls` | Zoznam výziev (admin) |
| `POST` | `/api/v1/admin/calls` | Vytvorenie výzvy |
| `PUT` | `/api/v1/admin/calls/{call}` | Aktualizácia výzvy |
| `DELETE` | `/api/v1/admin/calls/{call}` | Zmazanie výzvy |
| `PATCH` | `/api/v1/calls/{call}/workflow` | Zmena stavu výzvy |

#### Formuláre

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/v1/admin/calls/{call}/form-schemas` | Zoznam verzií formulára |
| `POST` | `/api/v1/admin/calls/{call}/form-schemas` | Vytvorenie formulára |
| `PUT` | `/api/v1/admin/form-schemas/{schema}` | Aktualizácia formulára |
| `GET` | `/api/v1/admin/form-schemas/{schema}/fields` | Polia formulára |
| `POST` | `/api/v1/admin/form-schemas/{schema}/fields` | Pridanie poľa |
| `PUT` | `/api/v1/admin/form-fields/{field}` | Aktualizácia poľa |
| `DELETE` | `/api/v1/admin/form-fields/{field}` | Zmazanie poľa |

#### Kritériá

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/v1/admin/criteria` | Zoznam kritérií |
| `POST` | `/api/v1/admin/criteria` | Vytvorenie kritéria |
| `PUT` | `/api/v1/admin/criteria/{criterion}` | Aktualizácia kritéria |
| `DELETE` | `/api/v1/admin/criteria/{criterion}` | Zmazanie kritéria |
| `GET` | `/api/v1/admin/calls/{call}/criteria` | Kritériá výzvy |
| `POST` | `/api/v1/admin/calls/{call}/criteria` | Priradenie kritérií k výzve |

---

## Integrácie

### Applications
- `Application.call_id` → `call.id`
- Pri podaní žiadosti sa overuje stav výzvy (musí byť `open`)
- `Application.form_schema_id` → `form_schema.id`

### Evaluation
- `CommissionMember.call_id` → `call.id`
- `call_commission_setup` pivot priradí komisiu k výzve

### Mentorship
- `Milestone.call_id` → `call.id` (míľniky sú viazané na výzvu, nie na žiadosť priamo)

### Reporting
- Export výziev cez `CallReport` (Reporting modul)

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Výzva/formulár načítaný |
| `201` | Výzva/formulár vytvorený |
| `403` | Nedostatočné oprávnenia (nie admin) |
| `404` | Výzva/formulár nenájdený |
| `422` | Validačná chyba |
| `409` | Výzva je zatvorená, nelze pridávať žiadosti |

---

*Modul Programs – NTI Backend | Laravel 12*
