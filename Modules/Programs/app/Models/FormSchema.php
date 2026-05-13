<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSchema extends Model
{
    protected $table = 'form_schema';

    protected $fillable = [
        'call_id',
        'version',
        'status',
        'title',
        'description',
        'sections',
        'meta',
        'published_at',
    ];

    protected $casts = [
        'sections' => 'array',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_schema_id')->orderBy('sort_order');
    }

    public static function publishedLatestForCall(int $callId): ?self
    {
        return static::query()
            ->where('call_id', $callId)
            ->where('status', 'published')
            ->orderByDesc('version')
            ->with(['formFields' => static function (Builder $q): void {
                $q->orderBy('sort_order');
            }])
            ->first();
    }

    public function resolveTitle(string $localeCode): string
    {
        $titles = $this->meta['titles'] ?? null;
        if (is_array($titles) && isset($titles[$localeCode]) && is_string($titles[$localeCode]) && $titles[$localeCode] !== '') {
            return $titles[$localeCode];
        }

        if (is_string($this->title) && $this->title !== '') {
            return $this->title;
        }

        return $localeCode === 'en' ? 'Application details' : 'Údaje prihlášky';
    }

    public function resolveDescription(string $localeCode): ?string
    {
        $descriptions = $this->meta['descriptions'] ?? null;
        if (is_array($descriptions) && isset($descriptions[$localeCode])) {
            $d = $descriptions[$localeCode];

            return is_string($d) && $d !== '' ? $d : null;
        }

        return is_string($this->description) && $this->description !== '' ? $this->description : null;
    }
}
