<?php

namespace Modules\Content\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Modules\Content\Models\SiteMember;
use Modules\Content\Policies\SiteMemberPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ContentServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        RateLimiter::for('contact', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->input('email')),
            ];
        });
    }

    protected string $name = 'Content';
    protected string $nameLower = 'content';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
        AuthServiceProvider::class
    ];
}
