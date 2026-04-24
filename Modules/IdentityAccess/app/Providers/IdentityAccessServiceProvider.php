<?php

namespace Modules\IdentityAccess\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\IdentityAccess\Models\ConsentType;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\UserConsent;
use Modules\IdentityAccess\Policies\ConsentTypePolicy;
use Modules\IdentityAccess\Policies\RolePolicy;
use Modules\IdentityAccess\Policies\UserConsentPolicy;
use Modules\IdentityAccess\Policies\UserPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class IdentityAccessServiceProvider extends ModuleServiceProvider
{

    public function boot(): void{
        $this->registerRateLimiters();
       //Add policies if they are not detected
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('auth.login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by(sha1($email.$request->ip())),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('auth.forgot', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(3)->by(sha1($email)),
                Limit::perMinute(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('auth.register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
    /**
     * The name of the module.
     */
    protected string $name = 'IdentityAccess';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'identityaccess';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];



    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
