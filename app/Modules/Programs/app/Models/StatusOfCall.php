<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOfCall extends Model
{
    protected $table = 'status_of_call';

    protected $fillable = [
        'name',
    ];
}
