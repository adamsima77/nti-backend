<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Content\Database\Factories\ContactSubmissionFactory;

class ContactSubmission extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'description',
        'is_solved'
    ];

    protected $casts = [
        'is_solved' => 'boolean',
    ];

    public function setSolved(): void
    {
        $this->update(['is_solved' => true]);
    }

    // protected static function newFactory(): ContactSubmissionFactory
    // {
    //     // return ContactSubmissionFactory::new();
    // }
}
