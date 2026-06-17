# KPI a Výstupy z Projektov - API Dokumentácia

## Prehľad

Module **Reporting** poskytuje REST API endpointy pre správu Key Performance Indicators (KPI) a Výstupov z projektov.

## Základné Info

- **Base URL**: `/api/`
- **Autentifikácia**: Vyžaduje `Authorization: Bearer {token}` (Sanctum)
- **Response format**: JSON
- **HTTP Status**: Štandardné HTTP kódy (200, 201, 400, 403, 404, 422)

---

## KPI Endpointy

### 1. Zoznam KPI pre aplikáciu

```http
GET /applications/{applicationId}/kpis?search=efficiency&page=1
Authorization: Bearer {token}
```

**Parametry:**
- `search` (string, optional) – Vyhľadávanie podľa názvu metriky
- `page` (integer, optional) – Stránkovanie, default: 1

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "application_id": 10,
      "metric_name": "Efficiency Score",
      "target_value": 85.00,
      "actual_value": 92.50,
      "unit": "%",
      "description": "Overall project efficiency",
      "achievement_percentage": 108.82,
      "target_met": true,
      "created_at": "2026-05-03T10:15:00Z",
      "updated_at": "2026-05-03T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

---

### 2. Detail KPI

```http
GET /kpis/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "application_id": 10,
    "metric_name": "Efficiency Score",
    "target_value": 85.00,
    "actual_value": 92.50,
    "unit": "%",
    "description": "Overall project efficiency",
    "achievement_percentage": 108.82,
    "target_met": true,
    "created_at": "2026-05-03T10:15:00Z",
    "updated_at": "2026-05-03T10:15:00Z"
  }
}
```

---

### 3. Vytvoriť KPI

```http
POST /applications/{applicationId}/kpis
Authorization: Bearer {token}
Content-Type: application/json

{
  "application_id": 10,
  "metric_name": "Efficiency Score",
  "target_value": 85,
  "actual_value": null,
  "unit": "%",
  "description": "Overall project efficiency metric"
}
```

**Validácia:**
- `application_id` – povinné, existujúca aplikácia
- `metric_name` – povinné, max 255 znakov
- `target_value` – povinné, číslo >= 0
- `actual_value` – voliteľné, číslo >= 0
- `unit` – voliteľné, max 50 znakov
- `description` – voliteľné, max 1000 znakov

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "application_id": 10,
    "metric_name": "Efficiency Score",
    "target_value": 85.00,
    "actual_value": null,
    "unit": "%",
    "description": "Overall project efficiency metric",
    "achievement_percentage": null,
    "target_met": false,
    "created_at": "2026-05-03T10:15:00Z",
    "updated_at": "2026-05-03T10:15:00Z"
  }
}
```

---

### 4. Aktualizovať KPI

```http
PATCH /kpis/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "actual_value": 92.5,
  "description": "Updated description"
}
```

**Response (200):**
Vracia aktualizovaný KPI.

---

### 5. Vymazať KPI

```http
DELETE /kpis/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "KPI bolo úspešne odstránené"
}
```

---

### 6. KPI Štatistiky

```http
GET /applications/{applicationId}/kpis/statistics
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "total_kpis": 5,
  "kpis_with_targets": 5,
  "kpis_with_actuals": 4,
  "targets_met": 3,
  "average_achievement": 95.4
}
```

---

## Výstup Endpointy

### 1. Zoznam Výstupov pre aplikáciu

```http
GET /applications/{applicationId}/outputs?status=pending&search=report&page=1
Authorization: Bearer {token}
```

**Parametry:**
- `status` (string, optional) – Filter: `pending`, `completed`, `delivered`
- `search` (string, optional) – Vyhľadávanie podľa názvu
- `page` (integer, optional) – Stránkovanie

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "application_id": 10,
      "output_name": "Final Report",
      "description": "Project final report",
      "output_type": "report",
      "status": "pending",
      "status_label": "Pending",
      "planned_delivery": "2026-06-15T00:00:00Z",
      "actual_delivery": null,
      "is_overdue": false,
      "is_on_time": true,
      "documents": [
        {"id": 5},
        {"id": 6}
      ],
      "created_at": "2026-05-03T10:15:00Z",
      "updated_at": "2026-05-03T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

---

### 2. Detail Výstupu

```http
GET /outputs/{id}
Authorization: Bearer {token}
```

**Response (200):**
Vracia úplný detail výstupu s dokumentami.

---

### 3. Vytvoriť Výstup

```http
POST /applications/{applicationId}/outputs
Authorization: Bearer {token}
Content-Type: application/json

{
  "application_id": 10,
  "output_name": "Final Report",
  "description": "Project final report",
  "output_type": "report",
  "status": "pending",
  "planned_delivery": "2026-06-15 23:59:59",
  "document_ids": [5, 6]
}
```

**Validácia:**
- `application_id` – povinné, existujúca aplikácia
- `output_name` – povinné, max 255 znakov
- `description` – voliteľné, max 2000 znakov
- `output_type` – voliteľné, max 100 znakov
- `status` – voliteľné, jeden z: `pending`, `completed`, `delivered`
- `planned_delivery` – voliteľné, formát: `Y-m-d H:i:s`, musí byť v budúcnosti
- `document_ids` – voliteľné, pole ID existujúcich dokumentov

**Response (201):**
Vracia vytvorený výstup.

---

### 4. Aktualizovať Výstup

```http
PATCH /outputs/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "completed",
  "actual_delivery": "2026-06-10 14:30:00"
}
```

**Response (200):**
Vracia aktualizovaný výstup.

---

### 5. Označiť Výstup ako Doručený

```http
POST /outputs/{id}/mark-as-delivered
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "status": "completed",
    "actual_delivery": "2026-05-03T12:00:00Z",
    ...
  }
}
```

**Poznámka:** Automaticky nastaví `status` na `completed` a `actual_delivery` na aktuálny čas.

---

### 6. Pripojiť Dokumenty

```http
POST /outputs/{id}/attach-documents
Authorization: Bearer {token}
Content-Type: application/json

{
  "document_ids": [5, 6, 7]
}
```

**Response (200):**
Vracia výstup s novými dokumentami.

---

### 7. Odpojiť Dokumenty

```http
POST /outputs/{id}/detach-documents
Authorization: Bearer {token}
Content-Type: application/json

{
  "document_ids": [5]
}
```

**Response (200):**
Vracia výstup bez odpojeného dokumentu.

---

### 8. Vymazať Výstup

```http
DELETE /outputs/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Výstup projektu bol úspešne odstránený"
}
```

---

### 9. Štatistiky Výstupov

```http
GET /applications/{applicationId}/outputs/statistics
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "total_outputs": 5,
  "pending": 2,
  "completed": 2,
  "delivered": 1,
  "on_time": 4,
  "overdue": 1
}
```

---

## Autorizácia

### Pravidlá prístupu (Policies):

**Operácia** | **Admin** | **Mentor** | **Creator** | **Ostatní**
---|---|---|---|---
Čítať KPI/Output | ✅ | ✅ (ak priradený) | ✅ | ❌
Vytvoriť KPI/Output | ✅ | ✅ | ✅ | ❌
Upraviť KPI/Output | ✅ | ✅ (ak priradený) | ✅ | ❌
Odstrániť KPI/Output | ✅ | ✅ (ak priradený) | ✅ | ❌

**Creator** = používateľ, ktorý vytvoril aplikáciu  
**Mentor** = mentor priradený k aplikácii cez mentorship

---

## Error Handling

### 400 - Bad Request
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "metric_name": ["Názov metriky je povinný."]
  }
}
```

### 403 - Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 - Not Found
```json
{
  "message": "Not found."
}
```

### 422 - Unprocessable Entity
```json
{
  "message": "Vybraná aplikácia neexistuje.",
  "errors": {
    "application_id": ["Vybraná aplikácia neexistuje."]
  }
}
```

---

## Príklady Použitia (cURL)

### Vytvoriť KPI
```bash
curl -X POST http://localhost:8000/api/applications/10/kpis \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "application_id": 10,
    "metric_name": "Efficiency Score",
    "target_value": 85,
    "unit": "%"
  }'
```

### Aktualizovať skutočnú hodnotu KPI
```bash
curl -X PATCH http://localhost:8000/api/kpis/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "actual_value": 92.5
  }'
```

### Vytvoriť Výstup
```bash
curl -X POST http://localhost:8000/api/applications/10/outputs \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "application_id": 10,
    "output_name": "Final Report",
    "output_type": "report",
    "status": "pending",
    "planned_delivery": "2026-06-15 23:59:59"
  }'
```

### Označiť Výstup ako Doručený
```bash
curl -X POST http://localhost:8000/api/outputs/1/mark-as-delivered \
  -H "Authorization: Bearer {token}"
```

---

## Poznámky

- Všetky timestamp polia sú v ISO 8601 formáte (UTC)
- Pagination: default `per_page=15`, max zalieži na konfigurácii
- Soft deletes sú implementované (zmazané záznamy nie sú zobrazené)
- Alle operácie sú zalogované v audit log pre GDPR compliance
