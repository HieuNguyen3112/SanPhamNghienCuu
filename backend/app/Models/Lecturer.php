<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Department;
use App\Models\Degree;
use App\Models\AcademicRank;
use App\Models\LecturerProfile;
use App\Models\LecturerPartyMembership;
use App\Models\LecturerLanguageProficiency;
use App\Models\LecturerWorkHistory;
use App\Models\LecturerTrainingHistory;

class Lecturer extends Model
{
    use HasFactory;

    protected $table = 'lecturers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // lecturers có created_at/updated_at

    /** Cho phép gán hàng loạt các cột hồ sơ giảng viên */
    protected $fillable = [
        'user_id',           // FK -> users.id (nullable)
        'code',              // mã giảng viên (unique)
        'full_name',
        'email',             // nullable để tránh trùng users.email
        'phone',
        'degree_id',         // FK -> degrees.id (nullable)
        'academic_rank_id',  // FK -> academic_ranks.id (nullable)
        'department_id',     // FK -> departments.id
        'active',            // tinyint(1)
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    /** Quan hệ */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function degree()
    {
        return $this->belongsTo(Degree::class, 'degree_id', 'id');
    }

    public function academicRank()
    {
        return $this->belongsTo(AcademicRank::class, 'academic_rank_id', 'id');
    }

    public function profile()
    {
        return $this->hasOne(LecturerProfile::class, 'lecturer_id', 'id');
    }

    public function partyMembership()
    {
        return $this->hasOne(LecturerPartyMembership::class, 'lecturer_id', 'id');
    }

    public function languageProficiencies()
    {
        return $this->hasMany(LecturerLanguageProficiency::class, 'lecturer_id', 'id')
            ->orderBy('language')
            ->orderByDesc('id');
    }

    public function workHistories()
    {
        return $this->hasMany(LecturerWorkHistory::class, 'lecturer_id', 'id')
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }

    public function trainingHistories()
    {
        return $this->hasMany(LecturerTrainingHistory::class, 'lecturer_id', 'id')
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }
}
