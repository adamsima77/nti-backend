<?php

namespace Modules\Applications\Models;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;

class Application extends Model
{
    use HasFactory;

    protected $table = 'application';

    public $timestamps = false;

    protected $fillable = [
        'submitted_at',
        'last_update',
        'call_id',
        'team_id',
        'created_by',
        'active_status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'last_update' => 'datetime',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusOfApplication::class, 'active_status');
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_has_application',
            'application_id',
            'document_id'
        )
            ->withPivot('type_of_application_id')
            ->withTimestamps();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id');
    }
}
