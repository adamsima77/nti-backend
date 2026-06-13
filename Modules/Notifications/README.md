# Modul Notifications – Dokumentácia

> In-app notifikácie, emailové šablóny a hromadné emaily na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [EmailTemplate – šablóny](#emailtemplate)
5. [App\Services\NotificationService](#notificationservice)
6. [Kontroléry a logika](#kontroléry-a-logika)
7. [API Routes](#api-routes)
8. [Integrácie](#integrácie)
9. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Notifications** zabezpečuje:

- Ukladanie in-app notifikácií do databázy (vlastný model, nie Laravel DatabaseNotification)
- Emailové šablóny s Blade-like syntaxou
- Hromadné emaily (bulk mail) s throttlingom
- Označovanie notifikácií ako prečítaných

> **Dôležité:** In-app notifikácie sú ukladané do vlastnej tabuľky `notifications` cez model `Modules\Notifications\Models\Notifications`. Tento modul **nevyužíva** štandardný Laravel `DatabaseNotification` systém.

---

## Adresárová štruktúra

```
Modules/Notifications/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── NotificationsController.php
│   │       └── EmailTemplateController.php
│   ├── Models/
│   │   ├── Notifications.php         # In-app notifikácie
│   │   └── EmailTemplate.php         # Email šablóny
│   ├── Policies/
│   └── Providers/
│       └── NotificationsServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Notifications

**Tabuľka:** `notifications`

Vlastný model pre in-app notifikácie. Každá notifikácia je priradená konkrétnemu používateľovi.

```
notifications
├── id
├── user_id                 (FK → users.id)
├── notification_category_id (FK → notification_category.id)
├── notifiable_type         (string, polymorphic)
├── notifiable_id           (unsignedBigInteger, polymorphic)
├── title                   (string)
├── body                    (text)
├── is_read                 (boolean, default: false)
└── timestamps
```

**Príklad vytvorenia notifikácie (cez NotificationService):**

```php
Notifications::create([
    'user_id'                  => $recipient->id,
    'notification_category_id' => $category->id,
    'notifiable_type'          => Application::class,
    'notifiable_id'            => $application->id,
    'title'                    => 'Stav žiadosti sa zmenil',
    'body'                     => 'Vaša žiadosť prešla do stavu V hodnotení.',
    'is_read'                  => false,
]);
```

---

### EmailTemplate

**Tabuľka:** `email_template`

```
email_template
├── id
├── slug                      (string, unique)   – identifikátor šablóny
├── subject                   (string)            – predmet emailu
├── body_html                 (text)              – HTML telo emailu
├── notification_category_id  (FK → notification_category.id)
├── is_active                 (boolean)
├── type                      (string)
└── timestamps
```

**Model:**

```php
class EmailTemplate extends Model
{
    protected $table = 'email_template';

    protected $fillable = [
        'slug', 'subject', 'body_html',
        'notification_category_id', 'is_active', 'type',
    ];
}
```

#### Syntaxe premenných v šablónach

Emailové šablóny používajú Blade-like syntax **s dolárovou značkou**:

```
{{ $variable }}
```

> **Dôležité:** Premenné v šablónach majú `$` prefix. Príklad: `{{ $userName }}`, `{{ $applicationTitle }}`, `{{ $statusName }}`.

#### Kľúčové metódy

```php
// Nájdenie šablóny podľa slugu
$template = EmailTemplate::findBySlug('application-status-changed');

// Renderovanie predmetu s dosadením premenných
$subject = $template->renderSubject(['userName' => 'Ján Novák']);
// výsledok: "Zdravím Ján Novák, vaša žiadosť..."

// Renderovanie HTML tela s dosadením premenných
$html = $template->render([
    'userName'       => 'Ján Novák',
    'statusName'     => 'V hodnotení',
    'applicationTitle' => 'InnoX Project',
]);
```

#### Scopy

```php
// Scope pre bulk email šablóny
EmailTemplate::query()->bulk()->get()
// Vracia len šablóny s type = 'bulk' alebo podobným identifikátorom
```

---

## NotificationService

> **Dôležité:** `NotificationService` sa nachádza v `App\Services\NotificationService`, **nie** v tomto module.

**Súbor:** `app/Services/NotificationService.php` (v hlavnom app/ adresári)

Servis vytvára `Notifications` záznamy a odosiela emaily. Je injektovaný cez DI v kontroléroch a iných servisoch.

### Dostupné metódy

| Metóda | Popis |
|--------|-------|
| `notifyAdminsApplicationSubmitted($application)` | Notifikuje adminov pri podaní žiadosti |
| `notifyAdminsEvaluationSubmitted($evaluation)` | Notifikuje adminov pri odovzdaní hodnotenia |
| `notifyEvaluatorAssigned($evaluation)` | Notifikuje hodnotiteľa pri pridelení |
| `notifyTeamApplicationStatusChange(...)` | Notifikuje tím pri zmene stavu žiadosti |

### Vnútorná logika

Každá metóda vytvorí záznamy v tabuľke `notifications`:

```php
// Príklad z NotificationService:
Notifications::create([
    'user_id'                  => $adminUser->id,
    'notification_category_id' => $category->id,
    'notifiable_type'          => Application::class,
    'notifiable_id'            => $application->id,
    'title'                    => 'Nová žiadosť bola podaná',
    'body'                     => "Žiadosť #{$application->reference} bola podaná.",
    'is_read'                  => false,
]);
```

---

## Kontroléry a logika

### NotificationsController

Spravuje in-app notifikácie pre prihlásených používateľov.

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam notifikácií aktuálneho používateľa |
| `markAllRead()` | Označenie všetkých ako prečítaných |
| `markRead($id)` | Označenie jednej notifikácie ako prečítanej |

### EmailTemplateController

Spravuje emailové šablóny.

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam šablón |
| `show($id)` | Detail šablóny |
| `update($id)` | Aktualizácia šablóny |
| `fetchByLang($lang)` | Šablóny podľa jazyka |
| `fetchAll()` | Všetky šablóny |

---

## API Routes

**Súbor:** `routes/api.php`

### Hromadný email (throttle: 3/min)

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/send-bulk-email` | Odoslanie hromadného emailu |

> Throttle 3 požiadavky za minútu bráni zneužitiu.

### In-app notifikácie (auth:sanctum + verified)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/notifications` | Zoznam notifikácií |
| `POST` | `/api/notifications/mark-all-read` | Označenie všetkých ako prečítaných |
| `PATCH` | `/api/notifications/{notification}/mark-read` | Označenie jednej ako prečítanej |

### Email šablóny (auth:sanctum + verified)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/email-templates` | Zoznam šablón |
| `GET` | `/api/email-templates/{template}` | Detail šablóny |
| `PUT` | `/api/email-templates/{template}` | Aktualizácia šablóny |
| `GET` | `/api/email-templates/lang/{lang}` | Šablóny podľa jazyka |
| `GET` | `/api/email-templates/all` | Všetky šablóny |

---

## Integrácie

### App\Services\NotificationService
- Servis vytvára záznamy `Notifications` modelu z tohto modulu
- Volá sa z `ApplicationController`, `EvaluationController` a `ApplicationWorkflowService`

### Applications
- `Notifications.notifiable_type = Application::class`
- `Notifications.notifiable_id` = ID žiadosti

### IdentityAccess
- `Notifications.user_id` → `users.id`
- Každý používateľ vidí len svoje notifikácie

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Notifikácie načítané / označené |
| `403` | Nedostatočné oprávnenia |
| `404` | Notifikácia nenájdená |
| `429` | Príliš veľa požiadaviek na bulk email (throttle 3/min) |
| `422` | Validačná chyba |

---

*Modul Notifications – NTI Backend | Laravel 12*
