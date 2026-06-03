<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    use HasFactory;

    protected $table = 'academic_records';

    protected $fillable = [
        'student_id',
        'transcript_file',
        'honor_declaration',
        'honor_declaration_signed_at',
    ];

    protected $casts = [
        'honor_declaration' => 'boolean',
        'honor_declaration_signed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
