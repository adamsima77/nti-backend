<?php

namespace Modules\IdentityAccess\Models;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Content\Models\NewsTranslation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Modules\IdentityAccess\Database\Factories\UserFactory;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\Notifications\Emails\VerifyEmailMail;
use Modules\Notifications\Notifications\VerifyEmail as VerifyEmailNotification;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\UserOrganization;
use Modules\Students\Models\Student;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamMember;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'status_id',
        'avatar',
        'job_position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return Storage::url($this->avatar);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ---- Relations ----

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function userConsents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function newsTranslations(): HasMany
    {
        return $this->hasMany(NewsTranslation::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'user_organization',
            'user_id',
            'organization_id'
        )
            ->using(UserOrganization::class)
            ->withPivot('organization_role');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'team_members',
            'user_id',
            'team_id'
        )
            ->using(TeamMember::class)
            ->withPivot('team_role_id');
    }

    // ---- Role checks ----

    public function isGuest(): bool
    {
        return $this->roles()->where('name', 'guest')->exists();
    }

    public function isStudent(): bool
    {
        return $this->roles()->where('name', 'student')->exists();
    }

    public function isTeamLeader(): bool
    {
        return $this->roles()->where('name', 'team_leader')->exists();
    }

    public function isPartner(): bool
    {
        return $this->roles()->whereIn('name', ['partner', 'organization'])->exists();
    }

    public function isMentor(): bool
    {
        return $this->roles()->where('name', 'mentor')->exists();
    }

    public function isEvaluator(): bool
    {
        return $this->roles()->where('name', 'evaluator')->exists();
    }

    public function isCMSEditor(): bool
    {
        return $this->roles()->where('name', 'cms_editor')->exists();
    }

    public function isAdmin(): bool
    {
        return $this->roles()->where('name', 'nti_admin')->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', 'nti_superadmin')->exists();
    }

    // ---- Additional methods ----

    public function setStatus(UserStatus $status): void
    {
        $this->status_id = $status->value;
        $this->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        $langId = request()->cookie('i18n_redirected', 'sk') === 'en' ? 2 : 1;
        $this->notify(new VerifyEmailNotification($langId));
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
