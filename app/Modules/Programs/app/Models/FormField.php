<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $table = 'form_field';

    protected $fillable = [
        'form_schema_id',
        'sort_order',
        'name',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function formSchema(): BelongsTo
    {
        return $this->belongsTo(FormSchema::class, 'form_schema_id');
    }
}
