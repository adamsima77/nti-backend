<?php

namespace Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model
{
    use HasFactory;

    protected $table = 'commission';

    protected $fillable = [
        'name',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(CommissionMember::class, 'commission_id');
    }
}