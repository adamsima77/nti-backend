<?php

namespace Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Applications\Models\Application;

class Evaluation extends Model
{
    use HasFactory;

    protected $table = 'evaluation';

    protected $fillable = [
        'application_id',
        'commission_member_id',
        'decision_id',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function commissionMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'commission_member_id');
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'decision_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class, 'evaluation_id');
    }
}
