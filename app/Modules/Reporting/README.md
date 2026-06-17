# Modul Reporting – Dokumentácia

> Dashboardy, KPI metriky, výstupy projektov, exporty a admin reporty na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [FormRequest triedy](#formrequest-triedy)
5. [Export systém](#export-systém)
6. [Kontroléry a logika](#kontroléry-a-logika)
7. [API Routes](#api-routes)
8. [Integrácie](#integrácie)
9. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Reporting** zabezpečuje:

- KPI metriky pre projekty (cieľové a skutočné hodnoty)
- Výstupy projektov (deliverables) s typmi a stavmi
- Asynchrónne exporty žiadostí do XLSX/CSV/PDF
- Dashboard počty pre adminov
- Reporty o uzavretí výzvy (callClosureReport)
- Logy a bezpečnostné záznamy pre super adminov

> **Dôležité:** Tento modul je **jediný**, ktorý obsahuje `FormRequest` triedy. Ostatné moduly FormRequesty nepoužívajú.

---

## Adresárová štruktúra

```
Modules/Reporting/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ExportController.php
│   │   │   ├── ProjectKpiController.php
│   │   │   ├── ProjectOutputController.php
│   │   │   └── ReportingController.php
│   │   └── Requests/                        # FormRequest triedy (len v tomto module)
│   │       ├── StoreProjectKpiRequest.php
│   │       └── StoreProjectOutputRequest.php
│   ├── Jobs/
│   │   └── GenerateExportRequestFileJob.php
│   ├── Models/
│   │   ├── ExportRequest.php
│   │   ├── ProjectKpi.php
│   │   └── ProjectOutput.php
│   ├── Policies/
│   └── Providers/
│       └── ReportingServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### ProjectKpi

**Tabuľka:** `project_kpi`

```
project_kpi
├── id
├── application_id  (FK → application.id)
├── metric_name     (string)
├── target_value    (decimal)
├── actual_value    (decimal, nullable)
├── unit            (string, nullable)
├── description     (text, nullable)
└── timestamps
```

**Model:**

```php
class ProjectKpi extends Model
{
    protected $table = 'project_kpi';

    protected $fillable = [
        'application_id', 'metric_name',
        'target_value', 'actual_value',
        'unit', 'description',
    ];
}
```

**Computed atribúty a metódy:**

```php
// Percento dosiahnutia KPI
// Vracia null ak target_value = 0 (delenie nulou)
public function getAchievementPercentageAttribute(): ?float
{
    if ($this->target_value == 0) {
        return null;
    }
    return ($this->actual_value / $this->target_value) * 100;
}

// Či bol cieľ splnený
public function isTargetMet(): bool
{
    return $this->actual_value !== null
        && $this->actual_value >= $this->target_value;
}
```

---

### ProjectOutput

**Tabuľka:** `project_output`

```
project_output
├── id
├── application_id    (FK → application.id)
├── output_name       (string)
├── description       (text, nullable)
├── output_type       (string, nullable)
├── status            (string: pending/completed/delivered)
├── planned_delivery  (timestamp, nullable)
├── actual_delivery   (timestamp, nullable)
└── timestamps
```

**Model:**

```php
class ProjectOutput extends Model
{
    protected $table = 'project_output';

    protected $fillable = [
        'application_id', 'output_name', 'description',
        'output_type', 'status', 'planned_delivery', 'actual_delivery',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `application()` | `BelongsTo` | `Application` |
| `documents()` | `BelongsToMany` | `Document` cez `document_has_project_output` |

**Metódy:**

```php
// Overenie, či je output meškajúci
public function isOverdue(): bool;

// Overenie, či je výstup doručený včas
public function isOnTime(): bool;

// Nastavenie stavu na 'delivered' a actual_delivery = now()
public function markAsDelivered(): void;

// Štítok stavu doručenia
public function getDeliveryStatusLabel(): string;
// Vracia: 'Pending' | 'Completed' | 'Delivered' | 'Unknown'
```

---

### ExportRequest

**Tabuľka:** `export_request`

```
export_request
├── id
├── user_id       (FK → users.id)
├── export_key    (string)             – slug/kľúč typu exportu
├── kind          (string: excel/pdf)
├── format        (string: xlsx/csv/pdf)
├── status        (string: pending/processing/completed/failed)
├── file_name     (string, nullable)
├── storage_disk  (string, nullable)
├── storage_path  (string, nullable)
├── meta          (json, nullable)     – extra parametre exportu
├── error_message (text, nullable)
├── queued_at     (timestamp, nullable)
├── processed_at  (timestamp, nullable)
├── completed_at  (timestamp, nullable)
├── failed_at     (timestamp, nullable)
└── timestamps
```

**Model:**

```php
class ExportRequest extends Model
{
    protected $fillable = [
        'user_id', 'export_key', 'kind', 'format',
        'status', 'file_name', 'storage_disk', 'storage_path',
        'meta', 'error_message', 'queued_at', 'processed_at',
        'completed_at', 'failed_at',
    ];

    protected $casts = [
        'meta'         => 'array',
        'queued_at'    => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at'    => 'datetime',
    ];
}
```

---

## FormRequest triedy

> Tento modul je **jediný** v celom projekte, ktorý obsahuje FormRequest triedy.

### StoreProjectKpiRequest

**Súbor:** `app/Http/Requests/StoreProjectKpiRequest.php`

```php
public function rules(): array
{
    return [
        'application_id' => ['required', 'exists:application,id'],
        'metric_name'    => ['required', 'max:255'],
        'target_value'   => ['required', 'numeric', 'min:0'],
        'actual_value'   => ['nullable'],
        'unit'           => ['nullable', 'max:50'],
        'description'    => ['nullable', 'max:1000'],
    ];
}
```

---

### StoreProjectOutputRequest

**Súbor:** `app/Http/Requests/StoreProjectOutputRequest.php`

```php
public function rules(): array
{
    return [
        'application_id'  => ['required', 'exists:application,id'],
        'output_name'     => ['required', 'max:255'],
        'description'     => ['nullable', 'max:2000'],
        'output_type'     => ['nullable', 'max:100'],
        'status'          => ['nullable', 'in:pending,completed,delivered'],
        'planned_delivery' => [
            'nullable',
            'date_format:Y-m-d H:i:s',
            'after_or_equal:now',
        ],
        'document_ids'    => ['nullable', 'array'],
        'document_ids.*'  => ['exists:document,id'],
    ];
}
```

---

## Export systém

### GenerateExportRequestFileJob

**Súbor:** `app/Jobs/GenerateExportRequestFileJob.php`

Job spracúva asynchrónne generovania exportov.

**Postup:**

1. Prijme `ExportRequest` model
2. Nastaví `status = 'processing'`, `processed_at = now()`
3. Generuje súbor podľa `kind`:
   - **`excel`** – cez `Maatwebsite\Excel`
   - **`pdf`** – cez `Barryvdh\DomPDF`
4. Uloží na `storage_disk` do cesty `exports/{user_id}/{slug}-{id}.{format}`
5. Nastaví `status = 'completed'`, `completed_at = now()`
6. Pri chybe: `status = 'failed'`, `failed_at = now()`, `error_message = ...`

**Cesta k súboru:**

```
exports/{user_id}/{export_key}-{export_request_id}.{format}
```

### Typy exportov

| export_key | Popis |
|------------|-------|
| `applications` | Export všetkých žiadostí |
| `call-report` | Report o výzve |
| `call-closure-report` | Report o uzavretí výzvy |
| `evaluations` | Export hodnotení |

---

## Kontroléry a logika

### ExportController

Obsluhuje exporty – vrátane exportov z modulu Applications (routes sú registrované v Applications module).

| Metóda | Popis |
|--------|-------|
| `showExportRequest($id)` | Detail ExportRequest záznamu |
| `downloadExportRequest($id)` | Stiahnutie vygenerovaného súboru |
| `callClosureReport()` | Spustenie reportu o uzavretí výzvy |
| `callReport()` | Spustenie výzva-reportu |
| `exportEvaluations()` | Spustenie exportu hodnotení |

### ProjectKpiController

| Metóda | Popis |
|--------|-------|
| `index()` | KPI pre danú žiadosť |
| `store()` | Vytvorenie KPI (používa StoreProjectKpiRequest) |
| `show($id)` | Detail KPI |
| `update($id)` | Aktualizácia KPI |
| `destroy($id)` | Zmazanie KPI |

### ProjectOutputController

| Metóda | Popis |
|--------|-------|
| `index()` | Výstupy pre danú žiadosť |
| `store()` | Vytvorenie výstupu (používa StoreProjectOutputRequest) |
| `show($id)` | Detail výstupu |
| `update($id)` | Aktualizácia výstupu |
| `destroy($id)` | Zmazanie výstupu |
| `markAsDelivered($id)` | Označenie ako doručené |
| `attachDocuments($id)` | Priradenie dokumentov |
| `detachDocuments($id)` | Odopnutie dokumentov |

### ReportingController

| Metóda | Popis |
|--------|-------|
| `adminDashboard()` | Dashboard počty pre admina |
| `superAdminDashboard()` | Štatistiky pre super admina |
| `securityLogs()` | Bezpečnostné logy (super admin) |

---

## API Routes

**Súbor:** `routes/api.php`

Všetky routes vyžadujú autentifikáciu (`auth:sanctum`) a overený email (`verified`).

### Exporty

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/exports/{id}` | Detail ExportRequest |
| `GET` | `/api/exports/{id}/download` | Stiahnutie exportu |
| `POST` | `/api/exports/call-closure-report` | Spustenie report uzavretia výzvy |
| `POST` | `/api/exports/call-report` | Spustenie reportu výzvy |
| `POST` | `/api/exports/evaluations` | Spustenie exportu hodnotení |

### KPI

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/project-kpis` | Zoznam KPI |
| `POST` | `/api/project-kpis` | Vytvorenie KPI |
| `GET` | `/api/project-kpis/{kpi}` | Detail KPI |
| `PUT` | `/api/project-kpis/{kpi}` | Aktualizácia KPI |
| `DELETE` | `/api/project-kpis/{kpi}` | Zmazanie KPI |

### Výstupy (Outputs)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/project-outputs` | Zoznam výstupov |
| `POST` | `/api/project-outputs` | Vytvorenie výstupu |
| `GET` | `/api/project-outputs/{output}` | Detail výstupu |
| `PUT` | `/api/project-outputs/{output}` | Aktualizácia |
| `DELETE` | `/api/project-outputs/{output}` | Zmazanie |
| `POST` | `/api/project-outputs/{output}/mark-delivered` | Označenie ako doručené |
| `POST` | `/api/project-outputs/{output}/documents/attach` | Priradenie dokumentov |
| `POST` | `/api/project-outputs/{output}/documents/detach` | Odopnutie dokumentov |

### Dashboardy

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/admin/dashboard` | Admin dashboard počty |
| `GET` | `/api/super-admin/dashboard` | Super admin štatistiky |
| `GET` | `/api/v1/admin/security/logs` | Bezpečnostné logy |

---

## Integrácie

### Applications
- `ProjectKpi.application_id` → `application.id`
- `ProjectOutput.application_id` → `application.id`
- `Application.kpis()` → `HasMany(ProjectKpi)`
- `Application.outputs()` → `HasMany(ProjectOutput)`
- `ExportController` obsluhuje export-related routes z Applications module

### AuditCompliance
- `securityLogs()` endpoint číta `SystemEvent` záznamy

### IdentityAccess
- `ExportRequest.user_id` → `users.id`

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | KPI/Output načítaný / export dostupný |
| `201` | KPI/Output vytvorený |
| `202` | Export zaradený do fronty |
| `403` | Nedostatočné oprávnenia |
| `404` | KPI/Output/Export nenájdený |
| `410` | Export súbor už nie je dostupný |
| `422` | Validačná chyba |

---

*Modul Reporting – NTI Backend | Laravel 12*
