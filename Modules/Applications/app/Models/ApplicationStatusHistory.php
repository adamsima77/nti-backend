<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    protected $fillable = [
        'status_of_application_id',
        'application_id',
        'note',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusOfApplication::class, 'status_of_application_id');
    }
}
