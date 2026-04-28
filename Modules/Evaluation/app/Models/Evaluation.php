<?php

namespace Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    use HasFactory;

    protected $table = 'evaluation';

    protected $fillable = [];

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'decision_id');
    }
}
