<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Blade;
use Modules\Notifications\Database\Factories\EmailTemplateFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'email_template';

    protected static function newFactory(): EmailTemplateFactory
    {
        return EmailTemplateFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'subject',
        'body_html',
        'notification_category_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NotificationCategory::class, 'notification_category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(EmailTemplateTranslation::class, 'email_template_id');
    }

    public function forLanguage(?int $languageId): self
    {
        $translation = $this->translations
            ->firstWhere('language_id', $languageId);

        if ($translation) {
            $this->subject   = $translation->subject;
            $this->body_html = $translation->body_html;
        }

        return $this;
    }

    public function renderSubject(array $data = []): string
    {
        if (!$this->subject) {
            return '';
        }

        return Blade::render($this->subject, $data);
    }

    public function render(array $data = []): string
    {
        if (!$this->body_html) {
            return '';
        }

        return Blade::render($this->body_html, $data);
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)
            ->where('is_active', true)
            ->with('translations')
            ->first();
    }
}
