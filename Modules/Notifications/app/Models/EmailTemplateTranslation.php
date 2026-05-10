<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notifications\Database\Factories\EmailTemplateTranslationFactory;

class EmailTemplateTranslation extends Model
{
    use HasFactory;

    protected $table = 'email_template_translation';

    protected static function newFactory(): EmailTemplateTranslationFactory
    {
        return EmailTemplateTranslationFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email_template_id',
        'language_id',
        'subject',
        'body_html',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
