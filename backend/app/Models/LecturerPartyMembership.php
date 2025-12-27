<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerPartyMembership extends Model
{
    protected $table = 'lecturer_party_memberships';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_id',
        'is_member',
        'membership_no',
        'joined_at',
        'official_at',
        'joining_place',
        'current_branch',
        'position',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_member' => 'bool',
        'joined_at' => 'date',
        'official_at' => 'date',
    ];
}
