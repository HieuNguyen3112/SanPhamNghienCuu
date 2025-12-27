<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerWorkHistory extends Model
{
    protected $table = 'lecturer_work_histories';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_id',
        'organization',
        'position',
        'department',
        'workplace',
        'start_date',
        'end_date',
        'is_current',
        'employment_type',
        'reason_for_leaving',
        'notes',
    ];

    protected $casts = [
        'is_current' => 'bool',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
