<?php

namespace Modules\AuditCompliance\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Illuminate\Support\Collection;

class GdprUserExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected User $user;
    protected string $userRoleType;

    public function __construct(User $user)
    {
        $this->user = $user;

        if ($this->user->roles->contains('name', 'partner')) {
            $this->userRoleType = 'partner';
        } elseif ($this->user->roles->contains('name', 'student')) {
            $this->userRoleType = 'student';
        } else {
            $this->userRoleType = 'generic';
        }
    }

    public function collection(): Collection
    {
        return collect([$this->user]);
    }

    private function formatConsents(User $user): string
    {
        if ($user->userConsents->isEmpty()) {
            return 'N/A';
        }

        return $user->userConsents
            ->map(fn ($uc) => $uc->consent?->name ?? 'Unknown')
            ->implode('; ');
    }

    /**
     * Resolves the translation by looking dynamically into the model's
     * specific translation collections.
     */
    private function translatedName($relation): string
    {
        if (! $relation) {
            return 'N/A';
        }

        $langId = LanguageType::ENGLISH->value;

        // Dynamically find any loaded relationship attribute ending with 'Translations'
        // (e.g., studyFieldTranslations, studyYearTranslations, studyProgramTranslations, sectorTranslations)
        $relations = $relation->getRelations();
        $translationCollection = null;

        foreach ($relations as $key => $value) {
            if (str_ends_with($key, 'Translations') && $value instanceof Collection) {
                $translationCollection = $value;
                break;
            }
        }

        if ($translationCollection && $translationCollection->isNotEmpty()) {
            return $translationCollection->firstWhere('language_id', $langId)?->name
                ?? $translationCollection->first()?->name
                ?? 'N/A';
        }

        // Direct fallback if attribute exists raw
        return $relation->name ?? 'N/A';
    }

    public function headings(): array
    {
        $baseHeadings = ['User ID', 'First Name', 'Last Name', 'Email', 'Role(s)', 'User Consents'];

        if ($this->userRoleType === 'partner') {
            return array_merge($baseHeadings, [
                'Organization Name', 'Organization Phone', 'Organization IČO', 'Organization Website',
                'Organization Description', 'Organization Country', 'Organization City', 'Organization Street',
                'Organization Postal Code', 'Organization Sectors', 'Account Created At',
            ]);
        }

        if ($this->userRoleType === 'student') {
            return array_merge($baseHeadings, [
                'Study Year', 'Study Program', 'University', 'Study Field', 'Account Created At',
            ]);
        }

        return array_merge($baseHeadings, ['Account Created At']);
    }

    public function map($user): array
    {
        $roles    = $user->roles->pluck('name')->implode(', ');
        $consents = $this->formatConsents($user);

        $row = [
            $user->id, $user->name, $user->surname, $user->email, $roles, $consents,
        ];

        if ($this->userRoleType === 'partner') {
            $org     = $user->organizations->first();
            $address = $org?->address;

            $sectors = 'N/A';
            if ($org && $org->sectors && $org->sectors->isNotEmpty()) {
                $sectors = $org->sectors->map(fn($s) => $this->translatedName($s))
                    ->filter(fn($val) => $val !== 'N/A')
                    ->implode(', ');

                if (empty($sectors)) { $sectors = 'N/A'; }
            }

            return array_merge($row, [
                $org?->name            ?? 'N/A',
                $org?->phone           ?? 'N/A',
                $org?->ico             ?? 'N/A',
                $org?->web_url         ?? 'N/A',
                $org?->description     ?? 'N/A',
                $address?->country     ?? 'N/A',
                $address?->city        ?? 'N/A',
                $address?->street      ?? 'N/A',
                $address?->postal_code ?? 'N/A',
                $sectors,
                $user->created_at->toDateTimeString(),
            ]);
        }

        if ($this->userRoleType === 'student') {
            $student = $user->student;

            return array_merge($row, [
                $this->translatedName($student?->studyYear),
                $this->translatedName($student?->studyProgram),
                $student?->university?->name ?? 'N/A',
                $this->translatedName($student?->studyField),
                $user->created_at->toDateTimeString(),
            ]);
        }

        return array_merge($row, [$user->created_at->toDateTimeString()]);
    }
}
