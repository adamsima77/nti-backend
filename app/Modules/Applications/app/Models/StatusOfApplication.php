<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOfApplication extends Model
{
    protected $table = 'status_of_application';

    protected $fillable = [
        'name',
    ];
}
