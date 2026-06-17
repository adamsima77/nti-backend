# Modul IdentityAccess – Dokumentácia

> Autentifikácia, registrácia, správa používateľov, rolí, súhlasov a pozvanie na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Autentifikácia a registrácia](#autentifikácia-a-registrácia)
5. [Role a oprávnenia](#role-a-oprávnenia)
6. [Email verifikácia](#email-verifikácia)
7. [Pozvania (Invitations)](#pozvania)
8. [Onboarding](#onboarding)
9. [Správa súhlasov (Consent)](#správa-súhlasov)
10. [API Routes](#api-routes)
11. [Turnstile CAPTCHA](#turnstile-captcha)
12. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **IdentityAccess** zabezpečuje celú autentifikačnú vrstvu platformy NTI:

- Registráciu nových používateľov (s CAPTCHA validáciou)
- Prihlásenie a odhlásenie pomocou Laravel Sanctum (Bearer token)
- Správu hesiel (zabudnuté heslo, reset hesla)
- Emailovú verifikáciu (MustVerifyEmail)
- Správu rolí (nti_admin, nti_superadmin, predseda_komisie, cms_editor, ...)
- Onboarding pre organizácie a študentov
- Pozvania do komisií a organizácií
- Správu používateľských súhlasov (GDPR consent)

---

## Adresárová štruktúra

```
Modules/IdentityAccess/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php                      # Login, register, verify, reset
│   │   │   ├── AcceptCommissionInviteController.php
│   │   │   ├── ConsentController.php
│   │   │   ├── InviteController.php
│   │   │   └── UserController.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── UserConsent.php
│   │   ├── UserStatus.php
│   │   └── UserProfile.php
│   ├── Notifications/
│   │   └── CustomVerifyEmail.php
│   ├── Policies/
│   │   └── UserPolicy.php
│   ├── Providers/
│   │   ├── IdentityAccessServiceProvider.php
│   │   └── EventServiceProvider.php
│   └── Rules/
│       └── TurnstileRule.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### User

**Tabuľka:** `users`

Model implementuje `MustVerifyEmail` – email verifikácia je povinná pre prístup k chráneným endpointom (middleware `verified`).

```php
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes;
}
```

**Kľúčové metódy kontroly roly:**

```php
$user->isAdmin();            // Kontroluje rolu 'nti_admin'
$user->isSuperAdmin();       // Kontroluje rolu 'nti_superadmin'
$user->isCommissionChair();  // Kontroluje rolu 'predseda_komisie'
$user->isCMSEditor();        // Kontroluje rolu 'cms_editor'
```

**Metóda pre odoslanie verifikácie s jazykovým nastavením:**

```php
public function sendEmailVerificationNotification(): void
{
    // Číta jazyk z cookie 'i18n_redirected'
    $lang = request()->cookie('i18n_redirected', 'sk');
    $this->notify(new CustomVerifyEmail($lang));
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `consents()` | `HasMany` | `UserConsent` |
| `organization()` | `BelongsToMany` | `Organization` |
| `student()` | `HasOne` | `Student` |
| `mentorships()` | `HasMany` | `Mentorship` |
| `notifications()` | `HasMany` | `Notifications` |

---

### UserStatus

**Tabuľka:** `user_status`

Číselník stavov používateľa. Kľúčová konštanta:

```php
class UserStatus extends Model
{
    const PENDING_EMAIL = 1;  // Čaká na verifikáciu emailu
}
```

---

### UserConsent

**Tabuľka:** `user_consent`

```
user_consent
├── id
├── user_id         (FK → users.id)
├── consent_id      (FK → consent_type.id)
├── granted         (boolean)
└── timestamps
```

> **Dôležité:** Stĺpec sa volá `consent_id` (nie `consent_type_id`) a pole pre udelenie súhlasu je `granted` (nie `is_granted`).

---

## Autentifikácia a registrácia

### Registrácia

**Endpoint:** `POST /api/auth/register`

**Validačné pravidlá (z `AuthController::register()`):**

```php
$request->validate([
    'email'                => ['required', 'email', 'unique:users,email'],
    'password'             => [
        'required', 'confirmed',
        Password::min(8)->mixedCase()->numbers()->symbols()
    ],
    'role'                 => ['required', 'in:student,partner'],
    'cf_turnstile_response' => [new TurnstileRule()],
]);
```

> **Dôležité:** Pole CAPTCHA sa volá `cf_turnstile_response` (nie `cf_turnstile_token`).

**Postup pri registrácii:**

1. Validácia vstupov vrátane CAPTCHA (TurnstileRule)
2. Vytvorenie záznamu `User` so `status_id = UserStatus::PENDING_EMAIL`
3. Priradenie roly (`student` alebo `partner`)
4. Vytvorenie `UserConsent` pre `privacy_policy`
5. Vytvorenie `UserConsent` pre `terms_of_service`
6. Odoslanie verifikačného emailu (`sendEmailVerificationNotification()`)
7. Vrátenie Sanctum Bearer tokenu

**Príklad registračnej požiadavky:**

```json
{
  "email": "jan@priklad.sk",
  "password": "HesloXY123!",
  "password_confirmation": "HesloXY123!",
  "role": "student",
  "cf_turnstile_response": "CLOUDFLARE_TURNSTILE_TOKEN"
}
```

---

### Prihlásenie

**Endpoint:** `POST /api/auth/login`

```json
{
  "email": "jan@priklad.sk",
  "password": "HesloXY123!"
}
```

**Odpoveď:**

```json
{
  "token": "1|abcdefghij...",
  "user": { ... }
}
```

Pri nesprávnych prihlasovacích údajoch vracia `401 Unauthorized`.

---

### Odhlásenie

**Endpoint:** `POST /api/logout`

```
Authorization: Bearer <token>
```

Zruší aktuálny Sanctum token. Vracia `200 OK`.

---

### Aktuálny používateľ

**Endpoint:** `GET /api/me`

Vracia detailné informácie o prihlásenom používateľovi vrátane: rolí, organizácie, profilu, súhlasov.

---

### Zabudnuté heslo

**Endpoint:** `POST /api/auth/forgot-password`

```json
{
  "email": "jan@priklad.sk"
}
```

Vždy vracia `200 OK` (z bezpečnostných dôvodov – neprezrádza, či email existuje).

---

### Reset hesla

**Endpoint:** `POST /api/auth/reset-password`

```json
{
  "token": "RESET_TOKEN_Z_EMAILU",
  "email": "jan@priklad.sk",
  "password": "NoveHeslo123!",
  "password_confirmation": "NoveHeslo123!"
}
```

---

## Role a oprávnenia

Správa rolí je implementovaná cez balíček `spatie/laravel-permission` s metódami dostupnými na modeli `User`.

### Dostupné roly v systéme

| Názov roly | Kontrolná metóda | Popis |
|------------|-----------------|-------|
| `nti_admin` | `$user->isAdmin()` | Správca platformy |
| `nti_superadmin` | `$user->isSuperAdmin()` | Superadministrátor |
| `predseda_komisie` | `$user->isCommissionChair()` | Predseda hodnotiacej komisie |
| `cms_editor` | `$user->isCMSEditor()` | Editor obsahu (CMS) |
| `student` | – | Registrovaný študent |
| `partner` | – | Partnerská organizácia |

> **Dôležité:** Názvy rolí sú uložené presne takto v databáze (mix slovenčiny a angličtiny).

### Overenie roly v kóde

```php
// Pomocné metódy na User modeli:
if ($user->isAdmin() || $user->isSuperAdmin()) {
    // Admin akcia
}

// Cez Spatie metódy priamo:
if ($user->hasRole('nti_admin')) { ... }
if ($user->hasAnyRole(['nti_admin', 'nti_superadmin'])) { ... }
```

### Spravovanie rolí – Admin Endpoints

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/users` | Zoznam všetkých používateľov |
| `GET` | `/api/users/{id}` | Detail používateľa |
| `POST` | `/api/users/{id}/roles` | Priradenie roly |
| `DELETE` | `/api/users/{id}/roles` | Odobratie roly |
| `PUT` | `/api/users/{id}/status` | Zmena statusu používateľa |

---

## Email verifikácia

### CustomVerifyEmail

**Súbor:** `app/Notifications/CustomVerifyEmail.php`

Vlastná notifikácia pre verifikáciu emailu. Generuje verifikačný URL s parametrom jazyka na základe cookie `i18n_redirected`.

```php
// User.php model – prepisuje štandardnú metódu MustVerifyEmail:
public function sendEmailVerificationNotification(): void
{
    $lang = request()->cookie('i18n_redirected', 'sk');
    $this->notify(new CustomVerifyEmail($lang));
}
```

Notifikácia akceptuje jazyk v konštruktore a vytvára localizovaný URL pre verifikáciu, ktorý je odoslaný emailom.

### Endpoints verifikácie

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/auth/resend-verification` | Znovu odoslanie verifikačného emailu |
| `GET` | `/api/auth/verify-email/{id}/{hash}` | Overenie emailovej adresy |

### Middleware `verified`

Endpoint vyžadujúci overený email:

```php
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Chránené endpointy
});
```

Ak email nie je overený, API vracia `403 Forbidden` s JSON chybou.

---

## Pozvania

### InviteController

**Súbor:** `app/Http/Controllers/InviteController.php`

Spravuje pozvania používateľov do organizácií.

### AcceptCommissionInviteController

**Súbor:** `app/Http/Controllers/AcceptCommissionInviteController.php`

Spravuje prijatie pozvania do hodnotiacej komisie. Endpoint je definovaný v IdentityAccess routes napriek tomu, že logicky patrí k modulu Evaluation.

### Endpoints pre pozvania

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/auth/invite` | Odoslanie pozvania do organizácie |
| `POST` | `/api/auth/commission-invite` | Odoslanie pozvania do komisie |
| `POST` | `/api/commission-invite/accept` | Prijatie pozvania do komisie |

---

## Onboarding

Po registrácii a verifikácii emailu musia používatelia dokončiť onboarding.

### Onboarding pre organizácie (partneri)

**Endpoint:** `POST /api/organization-onboarding`

Vytvára organizáciu prepojenú s používateľom. Vstup zahŕňa názov, IČO, webovú URL, popis, telefón, adresu a ID sektora.

### Onboarding pre študentov

**Endpoint:** `POST /api/student-onboarding`

Vytvára profil študenta prepojený s používateľom. Vstup zahŕňa `university_id`, `study_program_id`, `study_field_id`, `study_year_id`.

---

## Správa súhlasov

### ConsentController

**Súbor:** `app/Http/Controllers/ConsentController.php`

### Endpoints

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/consents` | Zoznam súhlasov aktuálneho používateľa |
| `POST` | `/api/consents/{id}` | Udelenie/aktualizácia súhlasu |
| `DELETE` | `/api/consents/{id}` | Odvolanie súhlasu |

### Vytvorenie súhlasov pri registrácii

```php
// V AuthController::register()
UserConsent::create([
    'user_id'    => $user->id,
    'consent_id' => $privacyPolicyConsentType->id,
    'granted'    => true,
]);

UserConsent::create([
    'user_id'    => $user->id,
    'consent_id' => $termsConsentType->id,
    'granted'    => true,
]);
```

---

## API Routes

**Súbor:** `routes/api.php`

### Verejné auth routes (bez autentifikácie)

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/auth/login` | Prihlásenie |
| `POST` | `/api/auth/register` | Registrácia |
| `POST` | `/api/auth/forgot-password` | Zabudnuté heslo |
| `POST` | `/api/auth/reset-password` | Reset hesla |
| `POST` | `/api/auth/resend-verification` | Znovu odoslanie verifikácie |
| `GET` | `/api/auth/verify-email/{id}/{hash}` | Overenie emailu |
| `POST` | `/api/auth/invite` | Pozvanie do organizácie |
| `POST` | `/api/auth/commission-invite` | Pozvanie do komisie |
| `POST` | `/api/commission-invite/accept` | Prijatie pozvania do komisie |

### Chránené routes (auth:sanctum)

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/logout` | Odhlásenie |
| `GET` | `/api/me` | Aktuálny používateľ |
| `POST` | `/api/organization-onboarding` | Onboarding organizácie |
| `POST` | `/api/student-onboarding` | Onboarding študenta |
| `GET` | `/api/users` | Zoznam používateľov (admin) |
| `GET` | `/api/users/{id}` | Detail používateľa |
| `POST` | `/api/users/{id}/roles` | Priradenie roly |
| `DELETE` | `/api/users/{id}/roles` | Odobratie roly |
| `PUT` | `/api/users/{id}/status` | Zmena statusu |
| `GET` | `/api/consents` | Zoznam súhlasov |
| `POST` | `/api/consents/{id}` | Udelenie súhlasu |
| `DELETE` | `/api/consents/{id}` | Odvolanie súhlasu |

---

## Turnstile CAPTCHA

### TurnstileRule

**Súbor:** `app/Rules/TurnstileRule.php`

Custom validačné pravidlo pre Cloudflare Turnstile CAPTCHA. Validuje token zaslaný frontendovou aplikáciou volaním Cloudflare Turnstile verify API.

```php
// Použitie v AuthController::register():
'cf_turnstile_response' => [new TurnstileRule()],
```

**Konfigurácia cez `.env`:**

```
TURNSTILE_SECRET=...
```

---

## Bezpečnostné aspekty

### Laravel Sanctum

- Bearer tokeny pre API autentifikáciu
- Tokeny sú ukladané v `personal_access_tokens`
- Pri odhlásení sa token okamžite zneplatní
- Token je vrátený iba pri registrácii a prihlásení

### Heslo Policy

```php
Password::min(8)->mixedCase()->numbers()->symbols()
```

- Minimálne 8 znakov
- Musí obsahovať veľké aj malé písmeno
- Musí obsahovať aspoň jedno číslo
- Musí obsahovať aspoň jeden špeciálny znak

### SoftDeletes

`User` model používa `SoftDeletes` – záznamy nie sú fyzicky mazané, len označené `deleted_at`.

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Úspešné prihlásenie, odhlásenie, verifikácia |
| `201` | Registrácia úspešná |
| `401` | Neplatné prihlasovacie údaje |
| `403` | Email nie je verifikovaný |
| `404` | Používateľ nenájdený |
| `422` | Validačná chyba |

### Typické chybové odpovede

```json
// Neplatné prihlasovacie údaje
{
  "message": "The provided credentials are incorrect."
}

// Neoverený email
{
  "message": "Your email address is not verified."
}

// CAPTCHA zlyhala
{
  "message": "The cf turnstile response field is invalid.",
  "errors": {
    "cf_turnstile_response": ["Neplatný Cloudflare Turnstile token."]
  }
}
```

---

*Modul IdentityAccess – NTI Backend | Laravel 12*
