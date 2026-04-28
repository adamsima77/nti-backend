<?php

namespace Modules\Evaluation\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Evaluation as EvaluationModel;
use Modules\Evaluation\Models\EvaluationScore;
use Modules\Evaluation\Models\Decision;
use Modules\Evaluation\Policies\CommissionPolicy;
use Modules\Evaluation\Policies\CommissionMemberPolicy;
use Modules\Evaluation\Policies\EvaluationPolicy;
use Modules\Evaluation\Policies\EvaluationScorePolicy;
use Modules\Evaluation\Policies\DecisionPolicy;

class EvaluationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Evaluation';
    protected string $nameLower = 'evaluation';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Commission::class, CommissionPolicy::class);
        Gate::policy(CommissionMember::class, CommissionMemberPolicy::class);
        Gate::policy(EvaluationModel::class, EvaluationPolicy::class);
        Gate::policy(EvaluationScore::class, EvaluationScorePolicy::class);
        Gate::policy(Decision::class, DecisionPolicy::class);
    }
}
