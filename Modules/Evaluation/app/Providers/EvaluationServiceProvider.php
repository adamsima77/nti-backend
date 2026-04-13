<?php

namespace Modules\Evaluation\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class EvaluationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Evaluation';
    protected string $nameLower = 'evaluation';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
