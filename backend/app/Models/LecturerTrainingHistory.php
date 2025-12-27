<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerTrainingHistory extends Model
{
    protected $table = 'lecturer_training_histories';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_id',
        'degree_id',
        'degree_title',
        'major',
        'institution',
        'country',
        'city',
        'start_date',
        'end_date',
        'is_current',
        'training_form',
        'funding_source',
        'certificate_no',
        'notes',
    ];

    protected $casts = [
        'is_current' => 'bool',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function degree()
    {
        return $this->belongsTo(Degree::class, 'degree_id', 'id');
    }
}
