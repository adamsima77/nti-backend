# Modul Organizations – Dokumentácia

> Správa organizácií, členstvá, pozvánky a sektory na platforme NTI.

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

Modul **Organizations** spravuje partnerské organizácie registrované na platforme. Každá organizácia môže:

- Byť vytvorená počas onboardingu (cez IdentityAccess modul)
- Mať členov (používateľov) s rôznymi rolami
- Byť prepojená s grantovými výzvami (cez `po_user_id` v Call)
- Prijímať pozvania pre nových členov
- Patriť do jedného alebo viacerých sektorov

---

## Adresárová štruktúra

```
Modules/Organizations/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── OrganizationController.php
│   │       ├── SectorController.php
│   │       └── AcceptInviteController.php
│   ├── Models/
│   │   ├── Organization.php
│   │   ├── OrganizationInvitation.php
│   │   └── Sector.php
│   ├── Policies/
│   │   └── OrganizationPolicy.php
│   └── Providers/
│       └── OrganizationsServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Organization

**Tabuľka:** `organization`

```
organization
├── id
├── name           (string)
├── phone          (string, E164PhoneNumberCast)
├── ico            (string)
├── web_url        (string, nullable)
├── description    (text, nullable)
├── address_id     (FK → address.id)
├── deleted_at     (softDeletes)
└── timestamps
```

> **Dôležité:** Pole IČO firmy sa volá `ico` (nie `registration_number`). Telefónne číslo má cast `E164PhoneNumberCast` z balíčka `Propaganistas\LaravelPhone`.

```php
class Organization extends Model
{
    use SoftDeletes;

    protected $table = 'organization';

    protected $fillable = [
        'name',
        'phone',   // ukladané a čítané v E.164 formáte (+421...)
        'ico',
        'web_url',
        'description',
        'address_id',
    ];

    protected $casts = [
        'phone' => E164PhoneNumberCast::class,
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `members()` | `BelongsToMany` | `User` cez `organization_has_user` |
| `sectors()` | `BelongsToMany` | `Sector` cez `sector_has_organization` |
| `address()` | `BelongsTo` | `Address` |
| `invitations()` | `HasMany` | `OrganizationInvitation` |

---

### OrganizationInvitation

**Tabuľka:** `organization_invitation`

```
organization_invitation
├── id
├── token                (string, unique)
├── email                (string)
├── organization_id      (FK → organization.id)
├── organization_role_id (FK → organization_role.id)
├── expires_at           (timestamp)
├── accepted_at          (timestamp, nullable)
└── timestamps
```

**Model:**

```php
class OrganizationInvitation extends Model
{
    protected $fillable = [
        'token',
        'email',
        'organization_id',
        'organization_role_id',
        'expires_at',
        'accepted_at',
    ];
}
```

**Pomocné metódy:**

```php
$invitation->isExpired();   // bool – expires_at < now()
$invitation->isAccepted();  // bool – accepted_at !== null
```

---

### Sector

**Tabuľka:** `sector`

Číselník sektorov (napr. IT, Zdravotníctvo, Vzdelávanie). Pivot tabuľka `sector_has_organization` prepája organizácie so sektormi.

---

## Kontroléry a logika

### OrganizationController

Hlavný kontrolér pre správu organizácií.

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam všetkých organizácií (admin) |
| `show($id)` | Detail organizácie |
| `store()` | Vytvorenie organizácie (admin) |
| `update($id)` | Aktualizácia organizácie |
| `destroy($id)` | Soft-delete organizácie |
| `myOrganization()` | Organizácia aktuálneho používateľa |
| `dashboard()` | Dashboard pre PO (program officer) |
| `backlog()` | Backlog žiadostí pre PO |
| `memberDashboard()` | Dashboard pre členov organizácie |
| `milestoneApprovals($call)` | Schvaľovanie míľnikov pre danú výzvu |
| `invite($id)` | Odoslanie pozvania novému členovi |
| `removeInvite($id)` | Zrušenie pozvania |

### AcceptInviteController

Prijatie pozvania do organizácie. Kontrolér je v module Organizations, ale cesta prijatia pozvánky je registrovaná v module IdentityAccess.

```php
// AcceptInviteController@accept
// Overuje token pozvania, priraďuje rolu, aktualizuje accepted_at
```

### SectorController

Správa sektorov. Endpoint pre verejné čítanie sektorov nevyžaduje autentifikáciu.

---

## API Routes

**Súbor:** `routes/api.php`

### Verejné endpointy

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/sectors` | Zoznam sektorov (bez auth) |

### Chránené endpointy (auth:sanctum + verified)

#### Organizácie (CRUD)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/organizations` | Zoznam organizácií |
| `GET` | `/api/organizations/{id}` | Detail organizácie |
| `POST` | `/api/organizations` | Vytvorenie organizácie |
| `PUT` | `/api/organizations/{id}` | Aktualizácia organizácie |
| `DELETE` | `/api/organizations/{id}` | Zmazanie organizácie |
| `GET` | `/api/my-organization` | Moja organizácia |

#### Pozvania a členovia

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/organizations/{id}/invite` | Odoslanie pozvania |
| `DELETE` | `/api/organizations/{id}/invite` | Zrušenie pozvania |

#### PO Dashboard (prefix `po/`)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/po/dashboard` | Dashboard pre program officer |
| `GET` | `/api/po/calls/{call}/milestone-approvals` | Schvaľovanie míľnikov |
| `GET` | `/api/backlog` | Backlog žiadostí |
| `GET` | `/api/member-dashboard` | Dashboard pre členov organizácie |

---

## E164PhoneNumberCast

Telefónne číslo organizácie je ukladané v E.164 formáte (napr. `+421912345678`). Cast automaticky konvertuje vstupný formát pri ukladaní a čítaní.

**Závislosť:** `propaganistas/laravel-phone`

```php
// Automatická konverzia pri ukladaní:
$organization->phone = '0912 345 678';
// Uložené ako: +421912345678

// Pri čítaní vracia PhoneNumber objekt:
$organization->phone->formatForCountry('SK');  // "0912 345 678"
$organization->phone->formatE164();            // "+421912345678"
```

---

## Integrácie

### Programs
- `Call.po_user_id` – priradený program officer (člen organizácie) k výzve

### Applications
- Organizácia podáva žiadosti cez tímy
- Väzba cez `User → Organization → Team → Application`

### IdentityAccess
- `AcceptInviteController` je v Organizations, ale route je registrovaná v IdentityAccess
- Onboarding organizácie (POST /organization-onboarding) je v IdentityAccess module

### Mentorship
- Program officers schvaľujú míľniky (GET /po/calls/{call}/milestone-approvals)

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Organizácia načítaná/aktualizovaná |
| `201` | Organizácia vytvorená |
| `403` | Nedostatočné oprávnenia (nie vlastník/admin) |
| `404` | Organizácia nenájdená |
| `410` | Pozvánka expirovala |
| `422` | Validačná chyba (neplatné IČO, neplatné tel. číslo) |

---

*Modul Organizations – NTI Backend | Laravel 12*
