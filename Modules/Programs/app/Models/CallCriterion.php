<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;

class CallCriterion extends Model
{
    protected $table = 'call_criterion';

    protected $fillable = [
        'name',
    ];
}
