# Modul AuditCompliance – Dokumentácia

> GDPR reporty, systémové udalosti, audit trail a compliance na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [GDPR Export (Job)](#gdpr-export-job)
5. [Kontroléry a logika](#kontroléry-a-logika)
6. [API Routes](#api-routes)
7. [Integrácie](#integrácie)
8. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **AuditCompliance** zabezpečuje:

- Generovanie GDPR exportov používateľských dát (PDF, XLSX, CSV)
- Sledovanie systémových udalostí a bezpečnostných alertov
- Audit trail pre citlivé operácie
- GDPR reporty s automatickým expiráciou po **15 minútach**

---

## Adresárová štruktúra

```
Modules/AuditCompliance/
├── app/
│   ├── Enums/
│   │   └── EventType.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── GdprReportController.php
│   │       └── SystemEventController.php
│   ├── Jobs/
│   │   └── ProcessGdprExport.php
│   ├── Models/
│   │   ├── GdprReport.php
│   │   └── SystemEvent.php
│   ├── Policies/
│   │   └── GdprReportPolicy.php
│   └── Providers/
│       └── AuditComplianceServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### GdprReport

**Tabuľka:** `gdpr_report`

```
gdpr_report
├── id
├── user_id         (FK → users.id)          – koho dáta sú exportované
├── requested_by    (FK → users.id)          – kto export vyžiadal
├── attachment_id   (FK → document.id, nullable) – odkaz na vygenerovaný súbor
├── status          (string: pending/processing/completed/failed)
├── expires_at      (timestamp, nullable)
├── downloaded_at   (timestamp, nullable)
└── deleted_at      (softDeletes)
```

**Model:**

```php
class GdprReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'requested_by', 'attachment_id',
        'status', 'expires_at', 'downloaded_at',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'downloaded_at' => 'datetime',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `user()` | `BelongsTo` | `User` cez `user_id` |
| `requestedBy()` | `BelongsTo` | `User` cez `requested_by` |
| `attachment()` | `BelongsTo` | `Document` (z Applications modulu) |

---

### SystemEvent

**Tabuľka:** `system_event`

```
system_event
├── id
├── event_type   (string, EventType enum)
├── severity     (string)
├── message      (text)
├── stack_trace  (text, nullable)
├── context      (json, nullable)
├── user_id      (FK → users.id, nullable)
├── ip_address   (string, nullable)
└── created_at   (timestamp)
```

> **Poznámka:** `timestamps = false` – tabuľka má len `created_at`, žiadne `updated_at`.

**Model:**

```php
class SystemEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type', 'severity', 'message',
        'stack_trace', 'context', 'user_id',
        'ip_address', 'created_at',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];
}
```

---

### EventType (Enum)

**Súbor:** `app/Enums/EventType.php`

```php
enum EventType: string
{
    case AUDIT          = 'AUDIT';
    case SECURITY_ALERT = 'SECURITY_ALERT';
    case SYSTEM_ERROR   = 'SYSTEM_ERROR';
}
```

---

## GDPR Export Job

### ProcessGdprExport

**Súbor:** `app/Jobs/ProcessGdprExport.php`

Job spracúva asynchrónne generovanie GDPR exportu. Spúšťa sa pri volaní `GdprReportController::generateReport()`.

**Konfigurácia:**

```php
class ProcessGdprExport implements ShouldQueue
{
    public int $tries   = 3;     // 3 pokusy
    public int $timeout = 120;   // 120 sekúnd timeout
}
```

### Postup generovania exportu

1. Prijme `GdprReport` model a cieľový formát
2. Nastaví `status = 'processing'`
3. Zozbiera dáta používateľa (profil, žiadosti, konzultácie, notifikácie)
4. Vygeneruje súbor podľa formátu:
   - **PDF** – cez `Barryvdh\DomPDF`
   - **XLSX** – cez `Maatwebsite\Excel`
   - **CSV** – cez `Maatwebsite\Excel`
5. Uloží súbor na disk `'local'` do adresára `gdpr_reports/`
6. Vytvorí `Document` a `DocumentVersion` záznamy so `security_classification = 'confidential'`
7. Nastaví `attachment_id` na GdprReport
8. **Nastaví `expires_at = now()->addMinutes(15)`** – report expiruje za 15 minút
9. Nastaví `status = 'completed'`

> **Kriticky dôležité:**
> - Súbory sú ukladané na disk `'local'` (nie `'private'` ani `'public'`)
> - Expirácia je **15 minút** (nie 30 dní)
> - Dokumenty majú `security_classification = 'confidential'`

### Cesta k súboru

```
Storage::disk('local')->path('gdpr_reports/{fileName}.{format}')
```

---

## Kontroléry a logika

### GdprReportController

**Súbor:** `app/Http/Controllers/GdprReportController.php`

| Metóda | HTTP Status | Popis |
|--------|-------------|-------|
| `generateReport()` | `202 Accepted` | Spustenie asynchrónneho GDPR exportu |
| `show($report)` | `200 OK` | Status/detail reportu |
| `download($report)` | `200 OK` / `410 Gone` | Stiahnutie reportu |

**`generateReport()` – validácia:**

```php
$request->validate([
    'target_user_id' => ['required', 'exists:users,id'],
    'format'         => ['required', 'in:pdf,csv,xlsx'],
]);
```

**`download()` – kontrola expirácie:**

```php
if ($report->expires_at && $report->expires_at->isPast()) {
    return response()->json([
        'message' => 'Report expiroval.'
    ], 410);  // HTTP_GONE
}
```

**Príklad odpovede na `generateReport()`:**

```http
HTTP/1.1 202 Accepted
Content-Type: application/json

{
  "message": "GDPR export bol zaradený do fronty.",
  "report_id": 42
}
```

### SystemEventController

Slúži na čítanie systémových udalostí pre super adminov.

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam systémových udalostí |
| `show($id)` | Detail udalosti |

---

## API Routes

**Súbor:** `routes/api.php`

Všetky routes vyžadujú autentifikáciu (`auth:sanctum`) a overený email (`verified`).

### GDPR Reporty (throttle: 20/min)

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/gdpr-reports/generate-report` | Spustenie GDPR exportu (202) |
| `GET` | `/api/gdpr-reports/{report}` | Status reportu |
| `GET` | `/api/gdpr-reports/{report}/download` | Stiahnutie reportu |

### Systémové udalosti (super admin)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/v1/admin/security/logs` | Systémové udalosti (z Reporting module) |

---

## Bezpečnostné aspekty

### Throttling

GDPR generovanie je obmedzené na **20 požiadaviek za minútu** (throttle:20,1).

### Expirácia reportov

- Report expiruje **15 minút** po vygenerovaní
- Po expirácii vracia endpoint download `410 Gone`
- Súbory nie sú automaticky mazané z disku – správca musí manuálne cleanup

### Klasifikácia dokumentov

Všetky GDPR súbory sú ukladané s `security_classification = 'confidential'`.

### Disk `'local'`

Súbory GDPR reportov sú ukladané na disk `'local'` – nedostupné priamo cez webový server. Prístup je výhradne cez autentifikovaný API endpoint.

---

## Integrácie

### Applications (Document model)
- `GdprReport.attachment_id` → `document.id`
- `Document` a `DocumentVersion` záznamy sú vytvárané v `ProcessGdprExport` jobe
- Dokumenty sú z Applications modulu

### IdentityAccess
- `GdprReport.user_id` → `users.id`
- `GdprReport.requested_by` → `users.id`
- Super admin môže žiadať export pre akéhokoľvek používateľa

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Report načítaný / stiahnutý |
| `202` | Export zaradený do fronty |
| `403` | Nedostatočné oprávnenia |
| `404` | Report nenájdený |
| `410` | Report expiroval (15 min) |
| `429` | Príliš veľa požiadaviek (throttle 20/min) |
| `422` | Validačná chyba (neplatný formát) |

---

*Modul AuditCompliance – NTI Backend | Laravel 12*
