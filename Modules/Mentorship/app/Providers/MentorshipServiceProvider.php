<?php

namespace Modules\Mentorship\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class MentorshipServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Mentorship';
    protected string $nameLower = 'mentorship';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
