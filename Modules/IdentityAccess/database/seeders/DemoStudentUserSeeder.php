<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

/**
 * Deterministický študentský účet na lokálne testovanie (login, /auth/me, tímy, prihlášky).
 *
 * Spustenie samostatne:
 *   php artisan db:seed --class="Modules\\IdentityAccess\\Database\\Seeders\\DemoStudentUserSeeder"
 *
 * Prihlásenie cez API vyžaduje overený e-mail a aktívny status — oboje seeder nastaví.
 * Frontend login môže stále vyžadovať Turnstile; pri teste API použite Sanctum token z Postmanu
 * alebo dočasne vypnite validáciu v dev.
 */
class DemoStudentUserSeeder extends Seeder
{
    public const EMAIL = 'jan.novak@test.nti.local';

    /** Heslo spĺňa bežné pravidlá (veľké/malé, číslo, symbol). */
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'student')->first();

        if (! $role) {
            $this->command?->error('Demo študent: rola `student` neexistuje. Najprv spustite RoleSeeder (IdentityAccessDatabaseSeeder).');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'          => 'jozko',
                'surname'       => 'mrkvicka',
                'password'      => self::PASSWORD,
                'status_id'     => UserStatus::ACTIVE->value,
                'job_position'  => 'Študent (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();

        $user->roles()->sync([$role->id]);

        $this->command?->newLine();
        $this->command?->info('Demo študent vytvorený / aktualizovaný:');
        $this->command?->table(
            ['Údaj', 'Hodnota'],
            [
                ['E-mail', self::EMAIL],
                ['Heslo', self::PASSWORD],
                ['Meno', 'Ján Novák'],
                ['Status', 'active (verified email)'],
                ['Rola', 'student'],
            ]
        );
    }
}
