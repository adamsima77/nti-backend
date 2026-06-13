# Modul Teams – Dokumentácia

> Správa tímov, členov, rolí a pozvánok na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Tímové roly](#tímové-roly)
5. [Kontroléry a logika](#kontroléry-a-logika)
6. [API Routes](#api-routes)
7. [Integrácie](#integrácie)
8. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Teams** spravuje projektové tímy, ktoré podávajú žiadosti na grantové výzvy. Každý tím:

- Má vedúceho a členov s priradenými rolami (v slovenčine)
- Môže pozvať nových členov cez email
- Je viazaný na žiadosti (`Application.team_id`)
- Môže generovať PDF s informáciami o tíme

---

## Adresárová štruktúra

```
Modules/Teams/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── TeamsController.php
│   │       └── TeamInvitationController.php
│   ├── Models/
│   │   ├── Team.php
│   │   ├── TeamMember.php
│   │   ├── TeamInvitation.php
│   │   └── TeamRole.php
│   ├── Policies/
│   │   └── TeamPolicy.php
│   └── Providers/
│       └── TeamsServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Team

**Tabuľka:** `team`

```
team
├── id
├── name        (string)
└── timestamps
```

> **Dôležité:** Jediné fillable pole je `name`. Ostatné vzťahy sú cez pivot tabuľky.

**Model:**

```php
class Team extends Model
{
    protected $table = 'team';

    protected $fillable = ['name'];  // len 'name' je fillable
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `members()` | `BelongsToMany` | `User` cez `team_members` |
| `applications()` | `HasMany` | `Application` |
| `invitations()` | `HasMany` | `TeamInvitation` |

---

### TeamMember

**Tabuľka:** `team_members`

```
team_members
├── user_id       (FK → users.id)
├── team_id       (FK → team.id)
└── team_role_id  (FK → team_role.id)
```

> **Poznámka:** `timestamps = false` – pivot tabuľka bez timestamps.

**Model:**

```php
class TeamMember extends Pivot
{
    protected $table = 'team_members';
    public $timestamps = false;

    protected $fillable = ['user_id', 'team_id', 'team_role_id'];
}
```

---

### TeamInvitation

**Tabuľka:** `team_invitation`

```
team_invitation
├── id
├── team_id       (FK → team.id)
├── email         (string)
├── token         (string, unique)
├── team_role_id  (FK → team_role.id)
├── invited_by    (FK → users.id)
├── expires_at    (timestamp)
├── accepted_at   (timestamp, nullable)
└── timestamps
```

**Model:**

```php
class TeamInvitation extends Model
{
    protected $table = 'team_invitation';

    protected $fillable = [
        'team_id', 'email', 'token',
        'team_role_id', 'invited_by',
        'expires_at', 'accepted_at',
    ];
}
```

---

### TeamRole

**Tabuľka:** `team_role`

Číselník rolí v tíme. Seedované hodnoty:

| Názov roly (name) | Popis |
|-------------------|-------|
| `Vedúci tímu` | Vedúci tímu – má plné práva nad tímom |
| `Člen tímu` | Člen tímu – základné oprávnenia |

> **Dôležité:** Názvy rolí sú v slovenčine. Kontrolér ich overuje podľa týchto presných stringov.

---

## Tímové roly

### Logika priraďovania rolí v TeamsController

**Súbor:** `app/Http/Controllers/TeamsController.php`

```php
private function resolveMemberRoleId(string $roleName): int
{
    $role = TeamRole::where('name', $roleName)->firstOrFail();

    // Vedúci tímu môže existovať len jeden – hodiť chybu ak sa pokúša pridať ďalšieho
    if ($role->name === 'Vedúci tímu') {
        throw new \Exception('Tím môže mať iba jedného vedúceho.');
    }

    return $role->id;
}
```

Pri pozvaní nového člena sa automaticky priradí rola `Člen tímu` (default):

```php
// Pri odoslaní pozvánky:
$defaultRole = TeamRole::where('name', 'Člen tímu')->firstOrFail();
```

---

## Kontroléry a logika

### TeamsController

Hlavný kontrolér pre správu tímov.

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam tímov aktuálneho používateľa |
| `show($id)` | Detail tímu |
| `store()` | Vytvorenie tímu (+ zaradenie tvorcu ako Vedúci tímu) |
| `update($id)` | Aktualizácia tímu |
| `destroy($id)` | Zmazanie tímu |
| `invite($team)` | Odoslanie pozvania novému členovi |
| `acceptInvitation()` | Prijatie pozvania (cez token) |
| `getMembers($team)` | Zoznam členov tímu |
| `addMember($team)` | Priame pridanie člena (admin) |
| `removeMember($team, $user)` | Odobratie člena |
| `generatePdf($team)` | Generovanie PDF s info o tíme |
| `formatTeamForStudent()` | Formátovanie tímu pre zobrazenie študentovi |

**`formatTeamForStudent()` – pomocná metóda:**

Táto metóda formátuje dáta tímu vrátane zoznamu členov a ich rolí na zobrazenie v dashboarde študenta.

### TeamInvitationController

Správa pozvánok.

| Metóda | Popis |
|--------|-------|
| `accept()` | Prijatie pozvania cez token |

---

## API Routes

**Súbor:** `routes/api.php`

Všetky routes vyžadujú autentifikáciu (`auth:sanctum`) a overený email (`verified`).

### Tímy

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/teams` | Zoznam tímov |
| `GET` | `/api/teams/{team}` | Detail tímu |
| `POST` | `/api/teams` | Vytvorenie tímu |
| `PUT` | `/api/teams/{team}` | Aktualizácia tímu |
| `DELETE` | `/api/teams/{team}` | Zmazanie tímu |

### Členovia

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/teams/{team}/members` | Zoznam členov |
| `POST` | `/api/teams/{team}/members` | Pridanie člena |
| `DELETE` | `/api/teams/{team}/members/{user}` | Odobratie člena |

### Pozvania

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/teams/{team}/invite` | Odoslanie pozvania |
| `POST` | `/api/invitations/accept` | Prijatie pozvania |

### PDF

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/teams/{team}/pdf` | Generovanie PDF tímu |

---

## Integrácie

### Applications
- `Application.team_id` → `team.id`
- Prístup k žiadosti majú len členovia tímu

### IdentityAccess
- `TeamMember.user_id` → `users.id`
- Oprávnenia vychádzajú z členstva v tíme a tímovej roly

### Reporting
- PDF tímu generuje TeamsController priamo (nie cez Reporting modul)

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Tím načítaný/aktualizovaný |
| `201` | Tím vytvorený |
| `403` | Nedostatočné oprávnenia (nie člen/vedúci) |
| `404` | Tím/člen nenájdený |
| `409` | Tím má už vedúceho (pri pokuse pridať druhého `Vedúci tímu`) |
| `410` | Pozvánka expirovala alebo bola už prijatá |
| `422` | Validačná chyba |

---

*Modul Teams – NTI Backend | Laravel 12*
