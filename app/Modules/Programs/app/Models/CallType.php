<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;

class CallType extends Model
{
    protected $table = 'call_type';

    protected $fillable = [
        'name',
    ];
}
