# Reporting Module

## Overview

The **Reporting** module handles KPI tracking and project outputs management for the NTI (Nitriansky technologický inkubátor) system. It provides comprehensive REST APIs for creating, managing, and analyzing project performance metrics and deliverables.

## Features

### 1. **KPI Management** (Key Performance Indicators)
- Create and track custom metrics for each application/project
- Set target values and actual values
- Calculate achievement percentage
- Get KPI statistics for applications
- Support for different units (%, EUR, count, etc.)

### 2. **Project Output Management**
- Define project deliverables/outputs
- Track output status (pending, completed, delivered)
- Monitor delivery timelines (planned vs actual)
- Detect overdue outputs
- Attach multiple documents to outputs
- Mark outputs as delivered

### 3. **Authorization & Security**
- Role-based access control (RBAC):
  - **Admin**: Full access to all KPIs and outputs
  - **Mentor**: Access to KPIs/outputs for assigned projects only
  - **Creator**: Access to their own project's KPIs and outputs
- Laravel Policies for fine-grained authorization
- Audit logging for all operations

## Architecture

```
Modules/Reporting/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ProjectKpiController.php
│   │   │   └── ProjectOutputController.php
│   │   ├── Requests/
│   │   │   ├── StoreProjectKpiRequest.php
│   │   │   ├── UpdateProjectKpiRequest.php
│   │   │   ├── StoreProjectOutputRequest.php
│   │   │   └── UpdateProjectOutputRequest.php
│   │   └── Resources/
│   │       ├── ProjectKpiResource.php
│   │       └── ProjectOutputResource.php
│   ├── Models/
│   │   ├── ProjectKpi.php
│   │   └── ProjectOutput.php
│   ├── Policies/
│   │   ├── ProjectKpiPolicy.php
│   │   └── ProjectOutputPolicy.php
│   ├── Providers/
│   │   ├── AuthServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   ├── ReportingServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Exports/
├── database/
│   └── migrations/
│       ├── 2026_05_03_090000_create_project_kpi_table.php
│       ├── 2026_05_03_090100_create_project_output_table.php
│       └── 2026_05_03_090200_create_document_has_project_output_table.php
├── routes/
│   ├── api.php
│   └── web.php
├── API_DOCUMENTATION.md
└── README.md
```

## Database Schema

### Tables

#### `project_kpi`
```sql
- id (PK)
- application_id (FK to application)
- metric_name (string)
- target_value (decimal)
- actual_value (decimal, nullable)
- unit (string, nullable) – e.g., '%', 'EUR', 'count'
- description (text, nullable)
- timestamps
```

#### `project_output`
```sql
- id (PK)
- application_id (FK to application)
- output_name (string)
- description (text, nullable)
- output_type (string, nullable)
- status (enum: pending, completed, delivered)
- planned_delivery (timestamp, nullable)
- actual_delivery (timestamp, nullable)
- timestamps
```

#### `document_has_project_output`
```sql
- document_id (FK, part of PK)
- project_output_id (FK, part of PK)
- created_at
```

## Models & Relations

### ProjectKpi Model
```php
// Relations
$kpi->application()  // BelongsTo Application

// Methods
$kpi->achievement_percentage  // Calculated property
$kpi->isTargetMet()          // Check if actual >= target
```

### ProjectOutput Model
```php
// Relations
$output->application()        // BelongsTo Application
$output->documents()          // BelongsToMany Document

// Methods
$output->isOverdue()          // Check if past planned date
$output->isOnTime()           // Check if actual <= planned
$output->markAsDelivered()    // Set status to 'completed'
$output->getDeliveryStatusLabel()
```

### Application Model Extensions
```php
// New relations added:
$application->kpis()     // HasMany ProjectKpi
$application->outputs()  // HasMany ProjectOutput
```

## API Endpoints

### KPI Endpoints
```
GET    /api/applications/{applicationId}/kpis
GET    /api/applications/{applicationId}/kpis/statistics
POST   /api/applications/{applicationId}/kpis
GET    /api/kpis/{id}
PATCH  /api/kpis/{id}
DELETE /api/kpis/{id}
```

### Output Endpoints
```
GET    /api/applications/{applicationId}/outputs
GET    /api/applications/{applicationId}/outputs/statistics
POST   /api/applications/{applicationId}/outputs
GET    /api/outputs/{id}
PATCH  /api/outputs/{id}
DELETE /api/outputs/{id}
POST   /api/outputs/{id}/mark-as-delivered
POST   /api/outputs/{id}/attach-documents
POST   /api/outputs/{id}/detach-documents
```

See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for complete API documentation with examples.

## Usage Examples

### Create a KPI

```php
// Controller example
$kpi = ProjectKpi::create([
    'application_id' => 10,
    'metric_name' => 'Efficiency Score',
    'target_value' => 85,
    'unit' => '%',
    'description' => 'Overall project efficiency',
]);
```

### Track KPI Progress

```php
// Update actual value
$kpi->update(['actual_value' => 92.5]);

// Check achievement
$achievement = $kpi->achievement_percentage;  // 108.82%
$metTarget = $kpi->isTargetMet();             // true
```

### Manage Project Outputs

```php
// Create output
$output = ProjectOutput::create([
    'application_id' => 10,
    'output_name' => 'Final Report',
    'output_type' => 'report',
    'status' => 'pending',
    'planned_delivery' => now()->addMonth(),
]);

// Attach documents
$output->documents()->sync([5, 6, 7]);

// Check status
$isOverdue = $output->isOverdue();
$isOnTime = $output->isOnTime();

// Mark as delivered
$output->markAsDelivered();
```

## Authorization Rules

| Operation | Admin | Mentor | Creator | Others |
|-----------|-------|--------|---------|--------|
| View | ✅ | ✅* | ✅ | ❌ |
| Create | ✅ | ✅ | ✅ | ❌ |
| Update | ✅ | ✅* | ✅ | ❌ |
| Delete | ✅ | ✅* | ✅ | ❌ |

*Only for projects they are assigned to as mentor

## Validation Rules

### ProjectKpiRequest
```php
'application_id' => 'required|integer|exists:application,id',
'metric_name' => 'required|string|max:255',
'target_value' => 'required|numeric|min:0',
'actual_value' => 'nullable|numeric|min:0',
'unit' => 'nullable|string|max:50',
'description' => 'nullable|string|max:1000',
```

### ProjectOutputRequest
```php
'application_id' => 'required|integer|exists:application,id',
'output_name' => 'required|string|max:255',
'description' => 'nullable|string|max:2000',
'output_type' => 'nullable|string|max:100',
'status' => 'nullable|in:pending,completed,delivered',
'planned_delivery' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:now',
'document_ids' => 'nullable|array|exists:document,id',
```

## Events & Listeners

Currently, the module triggers standard Laravel model events:
- `created` – When KPI or Output is created
- `updated` – When KPI or Output is updated
- `deleted` – When KPI or Output is deleted

These can be extended with custom listeners in the future for:
- Sending notifications
- Creating audit logs
- Triggering workflow transitions

## Testing

Unit and integration tests are located in:
```
tests/
├── Unit/
│   ├── ProjectKpiTest.php
│   └── ProjectOutputTest.php
└── Feature/
    ├── ProjectKpiApiTest.php
    └── ProjectOutputApiTest.php
```

Run tests:
```bash
php artisan test Modules/Reporting
```

## Configuration

The module requires no special configuration beyond standard Laravel setup. Ensure:
- Database migrations are run: `php artisan migrate`
- Sanctum is configured for API authentication
- AuthServiceProvider is registered in the module

## Integration with Other Modules

### Applications Module
- KPIs and Outputs are linked to `Application` model
- Creator and team context inherited from Application

### Mentorship Module
- Mentors have access to KPIs/Outputs for their assigned projects
- Mentorship relation used in Policy authorization

### Notifications Module (Future)
- Can be extended to send notifications on status changes
- Webhook triggers for external integrations

### AuditCompliance Module (Future)
- All operations can be logged for compliance
- User action tracking and data export support

## Development Roadmap

- [ ] Advanced KPI scoring algorithms
- [ ] Bulk operations for KPIs/Outputs
- [ ] Export to Excel/PDF reports
- [ ] KPI achievement trends and forecasting
- [ ] Notification system integration
- [ ] Webhooks for external systems
- [ ] Mobile app API optimization
- [ ] Real-time progress dashboards

## Troubleshooting

### 403 Forbidden
- User doesn't have permission for this KPI/Output
- Check if mentor is assigned or user is creator

### 404 Not Found
- Application, KPI, or Output doesn't exist
- Verify IDs in request

### 422 Unprocessable Entity
- Validation failed
- Check request body against FormRequest validation rules

### Soft Deleted Records
- Deleted KPIs/Outputs are not returned by default
- Use `withTrashed()` in queries to include them

## License

This module is part of the NTI project and follows the same license terms.

## Support

For issues or questions, refer to:
- [API Documentation](./API_DOCUMENTATION.md)
- Project technical specification
- Laravel Eloquent documentation
