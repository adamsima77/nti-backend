<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\IdentityAccess\Database\Factories\UserConsentFactory;

class UserConsent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'users_consents';

    protected $fillable = [
        'granted',
        'granted_at',
        'revoked_at',
        'ip',
        'user_agent',
        'user_id',
        'consent_id',
    ];

    protected static function newFactory(): UserConsentFactory
    {
         return UserConsentFactory::new();
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function consent(): BelongsTo{
        return $this->belongsTo(ConsentType::class);
    }
}
