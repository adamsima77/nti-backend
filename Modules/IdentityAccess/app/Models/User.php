<?php

namespace Modules\IdentityAccess\Models;
use Modules\IdentityAccess\Notifications\VerifyEmail;
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

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, SoftDeletes,HasApiTokens,Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */

    protected $table = 'users';

    //TODO: profile picture
    protected $fillable = [
          'name',
          'surname',
          'email',
          'password',
          'status_id'
    ];

    protected $hidden = [
         'password',
         'remember_token'

    ];
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
        $this->status = $status->value;
        $this->save();
    }


    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
