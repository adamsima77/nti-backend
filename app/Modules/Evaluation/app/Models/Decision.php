<?php

namespace Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    use HasFactory;

    protected $table = 'decision';

    protected $fillable = [
        'name',
    ];
}