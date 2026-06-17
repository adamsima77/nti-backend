<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
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

class DemoStudentUserSeeder extends Seeder
{
    public const EMAIL = 'jan.novak@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'student')->first();

        if (! $role) {
            $this->command?->error('Role `student` does not exist.');
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'         => 'Ján',
                'surname'      => 'Novák',
                'password'     => self::PASSWORD,
                'status_id'    => UserStatus::ACTIVE->value,
                'job_position' => 'Študent (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        /*
        |--------------------------------------------------------------------------
        | STUDENT RELATIONS
        |--------------------------------------------------------------------------
        */

        $studyProgram = $this->firstOrCreateTranslated(
            StudyProgram::class,
            'studyProgramTranslations',
            'Informatika',
            'Computer Science'
        );

        $studyField = $this->firstOrCreateTranslated(
            StudyField::class,
            'studyFieldTranslations',
            'Softvérové inžinierstvo',
            'Software Engineering'
        );

        $university = University::firstOrCreate(
            ['name' => 'Univerzita testovacia']
        );

        $studyYear = $this->firstOrCreateTranslated(
            StudyYear::class,
            'studyYearTranslations',
            '1. ročník (Bc.)',
            '1st year (Bachelor)'
        );

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT (CV)
        |--------------------------------------------------------------------------
        */

        $securityClassification = SecurityClassification::first()
            ?? SecurityClassification::create(['name' => 'internal']);

        $cv = Document::query()->firstOrCreate(
            ['owner_id' => $user->id],
            ['security_classification_id' => $securityClassification->id]
        );

        /*
        |--------------------------------------------------------------------------
        | STUDENT PROFILE
        |--------------------------------------------------------------------------
        */

        Student::query()->updateOrCreate(
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

        /*
        |--------------------------------------------------------------------------
        | OUTPUT
        |--------------------------------------------------------------------------
        */

        $this->command?->newLine();
        $this->command?->info('Demo student created/updated:');

        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Name', 'Ján Novák'],
                ['University', 'Test University'],
                ['Year', '1st year (Bachelor)'],
                ['Program', 'Computer Science'],
                ['Field', 'Software Engineering'],
                ['Portfolio', 'https://portfolio.example/jan-novak'],
                ['Status', 'active (verified email)'],
                ['Role', 'student'],
            ]
        );
    }

    /**
     * Create entity with model-specific translations.
     */
    private function firstOrCreateTranslated(
        string $model,
        string $relation,
        string $sk,
        string $en
    ) {
        $existing = $model::has($relation)->first();

        if ($existing) {
            return $existing;
        }

        $entity = $model::create();

        $entity->{$relation}()->createMany([
            [
                'language_id' => LanguageType::SLOVAK,
                'name' => $sk,
            ],
            [
                'language_id' => LanguageType::ENGLISH,
                'name' => $en,
            ],
        ]);

        return $entity;
    }
}
