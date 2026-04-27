<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    protected $table = 'criterion';

    protected $fillable = [
        'name',
    ];
}
