<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudyProgram;
use Modules\Students\Models\StudyField;
use Modules\Students\Models\University;
use Modules\Students\Models\StudyYear;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\SecurityClassification;

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
                'name'          => 'Ján',
                'surname'       => 'Novák',
                'password'      => self::PASSWORD,
                'status_id'     => UserStatus::ACTIVE->value,
                'job_position'  => 'Študent (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();

        $user->roles()->sync([$role->id]);

        // Ensure student-related reference data exists and pick sensible defaults
        $studyProgram = StudyProgram::first() ?? StudyProgram::create(['name' => 'Informatika']);
        $studyField = StudyField::first() ?? StudyField::create(['name' => 'Softvérové inžinierstvo']);
        $university = University::first() ?? University::create(['name' => 'Univerzita testovacia']);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => '1. ročník (Bc.)']);

        // Create a minimal CV document so cv_document_id satisfies DB constraints
        $securityClassification = SecurityClassification::where('name', 'internal')->first()
            ?? SecurityClassification::first()
            ?? SecurityClassification::create(['name' => 'internal']);

        $cv = Document::query()->firstOrCreate(
            ['owner_id' => $user->id],
            ['security_classification_id' => $securityClassification->id]
        );

        // Create or update student profile with as many fields filled as possible
        $student = Student::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'study_program_id' => $studyProgram->id,
                'study_field_id'   => $studyField->id,
                'university_id'    => $university->id,
                'study_year_id'    => $studyYear->id,
                'cv_document_id'   => $cv->id,
                'portfolio_url'    => 'https://portfolio.example/jan-novak',
            ]
        );

        $this->command?->newLine();
        $this->command?->info('Demo študent vytvorený / aktualizovaný:');
        $this->command?->table(
            ['Údaj', 'Hodnota'],
            [
                ['E-mail', self::EMAIL],
                ['Heslo', self::PASSWORD],
                ['Meno', 'Ján Novák'],
                ['Škola', $university->name],
                ['Ročník', $studyYear->name],
                ['Program', $studyProgram->name],
                ['Odbor', $studyField->name],
                ['Portfolio', 'https://portfolio.example/jan-novak'],
                ['Status', 'active (verified email)'],
                ['Rola', 'student'],
            ]
        );
    }
}
