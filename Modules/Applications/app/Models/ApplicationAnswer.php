<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Programs\Models\FormField;

class ApplicationAnswer extends Model
{
    protected $table = 'application_answer';

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'form_field_id',
        'value',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
