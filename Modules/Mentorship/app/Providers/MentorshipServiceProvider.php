<?php

namespace Modules\Mentorship\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Mentorship\Models\CallMilestone;
use Modules\Mentorship\Models\Mentorship;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Policies\CallMilestonePolicy;
use Modules\Mentorship\Policies\MentorshipPolicy;
use Modules\Mentorship\Policies\MilestonePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MentorshipServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Mentorship';
    protected string $nameLower = 'mentorship';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Milestone::class, MilestonePolicy::class);
        Gate::policy(Mentorship::class, MentorshipPolicy::class);
        Gate::policy(CallMilestone::class, CallMilestonePolicy::class);
    }
}
