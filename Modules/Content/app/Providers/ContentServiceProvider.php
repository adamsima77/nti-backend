<?php

namespace Modules\Content\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Content\Models\SiteMember;
use Modules\Content\Policies\SiteMemberPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ContentServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(SiteMember::class, SiteMemberPolicy::class);
    }

    protected string $name = 'Content';
    protected string $nameLower = 'content';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
