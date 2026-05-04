<?php

namespace Modules\AuditCompliance\Providers;

use Modules\Applications\Models\Application;
use Modules\AuditCompliance\Observers\ApplicationObserver;
use Modules\AuditCompliance\Observers\StudentObserver;
use Modules\AuditCompliance\Observers\UserObserver;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\Student;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuditComplianceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AuditCompliance';
    protected string $nameLower = 'auditcompliance';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        User::observe(UserObserver::class);
        Student::observe(StudentObserver::class);
        Application::observe(ApplicationObserver::class);
    }
}
