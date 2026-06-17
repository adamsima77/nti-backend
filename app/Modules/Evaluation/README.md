# Modul Evaluation – Dokumentácia

> Hodnotenia žiadostí, hodnotiace komisie, skóre a rozhodnutia na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Hodnotiaci workflow](#hodnotiaci-workflow)
5. [Kontroléry a logika](#kontroléry-a-logika)
6. [API Routes](#api-routes)
7. [Integrácie](#integrácie)
8. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Evaluation** spravuje hodnotenie žiadostí odbornou komisiou:

- Vytváranie a správu hodnotiacich komisií
- Pridávanie hodnotiteľov (CommissionMember) k výzvam
- Prideľovanie žiadostí hodnotiteľom
- Ukladanie hodnotení vrátane skóre, rozhodnutí a interných poznámok
- Workflow prechod žiadosti do stavu `V hodnotení`

---

## Adresárová štruktúra

```
Modules/Evaluation/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── EvaluationController.php
│   │       └── CommissionController.php
│   ├── Models/
│   │   ├── Evaluation.php
│   │   ├── Commission.php
│   │   ├── CommissionMember.php
│   │   └── Decision.php
│   ├── Policies/
│   │   └── EvaluationPolicy.php
│   └── Providers/
│       └── EvaluationServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Commission

**Tabuľka:** `commission`

```
commission
├── id
├── name        (string)
└── timestamps
```

> **Dôležité:** Jediné fillable pole je `name`.

**Model:**

```php
class Commission extends Model
{
    protected $table = 'commission';

    protected $fillable = ['name'];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `members()` | `HasMany` | `CommissionMember` |
| `calls()` | `BelongsToMany` | `Call` cez `call_commission_setup` |

---

### CommissionMember

**Tabuľka:** `commission_member`

```
commission_member
├── id
├── user_id        (FK → users.id)
├── commission_id  (FK → commission.id)
└── call_id        (FK → call.id)
```

> **Dôležité:** `timestamps = false` – žiadne `created_at`/`updated_at`. Má tri FK stĺpce vrátane `call_id`.

**Model:**

```php
class CommissionMember extends Model
{
    protected $table = 'commission_member';
    public $timestamps = false;

    protected $fillable = ['user_id', 'commission_id', 'call_id'];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `user()` | `BelongsTo` | `User` |
| `commission()` | `BelongsTo` | `Commission` |
| `call()` | `BelongsTo` | `Call` |
| `evaluations()` | `HasMany` | `Evaluation` |

---

### Evaluation

**Tabuľka:** `evaluation`

```
evaluation
├── id
├── application_id        (FK → application.id)
├── commission_member_id  (FK → commission_member.id)
├── decision_id           (FK → decision.id, nullable)
├── submitted_at          (timestamp, nullable)
├── internal_note         (text, nullable)
└── timestamps
```

**Model:**

```php
class Evaluation extends Model
{
    protected $table = 'evaluation';

    protected $fillable = [
        'application_id',
        'commission_member_id',
        'decision_id',
        'submitted_at',
        'internal_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `application()` | `BelongsTo` | `Application` |
| `commissionMember()` | `BelongsTo` | `CommissionMember` |
| `decision()` | `BelongsTo` | `Decision` |
| `scores()` | `HasMany` | `EvaluationScore` |

---

### Decision

**Tabuľka:** `decision`

Číselník rozhodnutí pri hodnotení (napr. Schváliť, Zamietnuť, Vyžiadať doplnenie).

---

## Hodnotiaci workflow

### Postup hodnotenia

```
1. Admin priradí komisiu k výzve (cez call_commission_setup)
2. Admin pridá hodnotiteľov do komisie (CommissionMember – s call_id)
3. Admin zmení stav žiadosti na 'V hodnotení' (cez ApplicationStateMachine)
4. NotificationService notifikuje hodnotiteľa o pridelení
5. Hodnotiteľ vidí pridelené žiadosti (GET /evaluations/pending)
6. Hodnotiteľ uloží skóre pre žiadosť (PATCH /evaluations/{application_id}/score)
7. Hodnotiteľ odošle hodnotenie (submitted_at = now())
8. Admin dostane notifikáciu (notifyAdminsEvaluationSubmitted)
9. Admin zmení stav žiadosti na 'Schválené' alebo 'Zamietnuté'
```

### Kľúčová logika fetchForEvaluator

Z `EvaluationController::fetchForEvaluator()`:

```php
// 1. Nájdi CommissionMember záznamy pre aktuálneho používateľa
$commissionMembers = CommissionMember::where('user_id', $user->id)->get();

// 2. Zoznam hodnotení pre daného hodnotiteľa
$evaluations = Evaluation::whereIn(
    'commission_member_id',
    $commissionMembers->pluck('id')
)->get();
```

---

## Kontroléry a logika

### EvaluationController

| Metóda | Popis |
|--------|-------|
| `fetchForEvaluator()` | Hodnotenia pridelené aktuálnemu hodnotiteľovi |
| `fetchCommittes()` | Zoznam všetkých komisií |
| `scoreApplication($application_id)` | Uloženie/aktualizácia skóre pre žiadosť |
| `submitEvaluation($evaluation)` | Odovzdanie hodnotenia (nastaví submitted_at) |
| `show($id)` | Detail hodnotenia |
| `getEvaluationsForApplication($applicationId)` | Hodnotenia pre konkrétnu žiadosť |

**`fetchCommittes()` vracia všetky komisie** (nie len komisie aktuálneho používateľa).

### CommissionController

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam komisií (admin) |
| `show($id)` | Detail komisie s členmi |
| `store()` | Vytvorenie komisie |
| `update($id)` | Aktualizácia komisie |
| `destroy($id)` | Zmazanie komisie |
| `addMember($commission)` | Pridanie člena do komisie |
| `removeMember($commission, $user)` | Odobratie člena z komisie |

---

## API Routes

**Súbor:** `routes/api.php`

### Admin endpointy – komisie (auth:sanctum + verified)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/v1/admin/commissions` | Zoznam komisií |
| `GET` | `/api/v1/admin/commissions/{commission}` | Detail komisie |
| `POST` | `/api/v1/admin/commissions` | Vytvorenie komisie |
| `PUT` | `/api/v1/admin/commissions/{commission}` | Aktualizácia komisie |
| `DELETE` | `/api/v1/admin/commissions/{commission}` | Zmazanie komisie |
| `POST` | `/api/v1/admin/commissions/{commission}/members` | Pridanie člena |
| `DELETE` | `/api/v1/admin/commissions/{commission}/members/{user}` | Odobratie člena |

### Hodnotiteľ endpointy

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/evaluations/pending` | Čakajúce hodnotenia |
| `PATCH` | `/api/evaluations/{application_id}/score` | Uloženie skóre |
| `GET` | `/api/evaluator/evaluations` | Hodnotenia hodnotiteľa |
| `GET` | `/api/evaluator/applications/{id}` | Detail žiadosti pre hodnotiteľa |
| `POST` | `/api/evaluator/submit/{evaluation}` | Odovzdanie hodnotenia |

### Všeobecné

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/commissions` | Všetky komisie (fetchCommittes) |
| `GET` | `/api/evaluations/{applicationId}` | Hodnotenia žiadosti |

---

## Integrácie

### Applications
- `Evaluation.application_id` → `application.id`
- Stav žiadosti musí byť `V hodnotení` alebo vyšší

### Programs
- `CommissionMember.call_id` → `call.id` (hodnotiteľ je priradený k výzve)
- `call_commission_setup` pivot: komisie sú priradené k výzvam

### IdentityAccess
- `AcceptCommissionInviteController` je definovaný v IdentityAccess module
- `CommissionMember.user_id` → `users.id`

### App\Services\NotificationService
- `notifyAdminsEvaluationSubmitted($evaluation)` – pri odovzdaní hodnotenia
- `notifyEvaluatorAssigned($evaluation)` – pri pridelení hodnotiteľa

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Hodnotenie načítané/uložené |
| `201` | Hodnotenie vytvorené |
| `403` | Nie je hodnotiteľom pre danú žiadosť |
| `404` | Hodnotenie/žiadosť nenájdená |
| `409` | Hodnotenie už bolo odovzdané (submitted_at je vyplnené) |
| `422` | Validačná chyba |

---

*Modul Evaluation – NTI Backend | Laravel 12*
