<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'statuses';
    protected $fillable = [
        'name'
    ];


    public function users():HasMany{
        return $this->hasMany(User::class);
    }
}
