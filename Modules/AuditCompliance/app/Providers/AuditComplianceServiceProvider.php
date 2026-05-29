<?php

namespace Modules\AuditCompliance\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Applications\Models\Application;
use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\AuditCompliance\Observers\ApplicationObserver;
use Modules\AuditCompliance\Observers\CallObserver;
use Modules\AuditCompliance\Observers\EvaluationObserver;
use Modules\AuditCompliance\Observers\OrganizationObserver;
use Modules\AuditCompliance\Observers\StudentObserver;
use Modules\AuditCompliance\Observers\TeamObserver;
use Modules\AuditCompliance\Observers\UserConsentObserver;
use Modules\AuditCompliance\Observers\UserObserver;
use Modules\AuditCompliance\Policies\AuditEventPolicy;
use Modules\Evaluation\Models\Evaluation;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\UserConsent;
use Modules\Organizations\Models\Organization;
use Modules\Programs\Models\Call;
use Modules\Students\Models\Student;
use Modules\Teams\Models\Team;
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
        Organization::observe(OrganizationObserver::class);
        Call::observe(CallObserver::class);
        Evaluation::observe(EvaluationObserver::class);
        Team::observe(TeamObserver::class);
        UserConsent::observe(UserConsentObserver::class);

        Gate::policy(AuditCompliance::class, AuditEventPolicy::class);

        $this->loadViewsFrom(module_path('AuditCompliance', 'Resources/Views'), 'audit-compliance');
    }
}
