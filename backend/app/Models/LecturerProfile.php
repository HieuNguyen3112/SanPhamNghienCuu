<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerProfile extends Model
{
    protected $table = 'lecturer_profiles';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_id',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'ethnicity',
        'hometown',
        'personal_email',
        'alternate_phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'current_position',
        'current_unit',
        'research_area',
        'teaching_specialization',
        'orcid_id',
        'google_scholar_profile',
        'research_gate_profile',
        'scopus_id',
        'publons_id',
        'personal_website',
        'academic_portfolio_url',
    ];
}
