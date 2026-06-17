<?php

namespace Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Programs\Models\Criterion;

class EvaluationScore extends Model
{
    use HasFactory;

    protected $table = 'evaluation_score';

    protected $fillable = [
        'evaluation_id',
        'criterion_id',
        'score',
        'comment',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_id');
    }
}