<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerLanguageProficiency extends Model
{
    protected $table = 'lecturer_language_proficiencies';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_id',
        'language',
        'proficiency_level',
        'is_native',
        'certificate_name',
        'certificate_level',
        'certificate_score',
        'issued_by',
        'issued_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'is_native' => 'bool',
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];
}
