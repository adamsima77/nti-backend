<?php

namespace Modules\IdentityAccess\Models;
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
use Modules\Notifications\Notifications\VerifyEmail;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\UserOrganization;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, SoftDeletes,HasApiTokens,Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */

    protected $table = 'users';

    protected $fillable = [
          'name',
          'surname',
          'email',
          'password',
          'status_id',
          'avatar'
    ];

    protected $hidden = [
         'password',
         'remember_token'
    ];

    protected $appends = [
        'avatar_url'
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
            'password' => 'hashed',
        ];
    }

    //Eloquent relations
    public function status(): belongsTo{
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

    public function newsTranslations(): HasMany{
        return $this->hasMany(NewsTranslation::class);
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

    //Check roles
    public function isGuest(): bool{
        return $this->roles()->where('name', 'návštevník')->exists();
    }

    public function isStudent(): bool{
        return $this->roles()->where('name', 'študent')->exists();
    }

    public function isTeamLeader(): bool{
        return $this->roles()->where('name', 'vedúci tímu')->exists();
    }

    public function isPartner(): bool{
        return $this->roles()->where('name', 'partner')->exists();
    }

    public function isMentor(): bool{
        return $this->roles()->where('name', 'mentor')->exists();
    }

    public function isEvaluator(): bool{
        return $this->roles()->where('name', 'hodnotiteľ')->exists();
    }

    public function isCMSEditor(): bool{
        return $this->roles()->where('name', 'editor obsahu')->exists();
    }

    public function isAdmin(): bool{
        return $this->roles()->where('name', 'nti administrátor')->exists();
    }

    public function isSuperAdmin(): bool{
        return $this->roles()->where('name', 'super administrátor')->exists();
    }

    //Additional methods
    public function setStatus(UserStatus $status): void
    {
        $this->status_id = $status->value;
        $this->save();
    }


    public function sendEmailVerificationNotification(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(15),
            [
                'id' => $this->id,
                'hash' => sha1($this->email),
            ]
        );

        Mail::to($this->email)->send(new VerifyEmailMail($verificationUrl, $this));
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
