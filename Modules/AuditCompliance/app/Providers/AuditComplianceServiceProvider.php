<?php

namespace Modules\AuditCompliance\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class AuditComplianceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AuditCompliance';
    protected string $nameLower = 'auditcompliance';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
