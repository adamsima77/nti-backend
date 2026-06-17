<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Model;

class TypeOfApplication extends Model
{
    protected $table = 'type_of_application';

    protected $fillable = [
        'name',
    ];
}
