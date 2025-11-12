<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $table = 'lecturers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;          // lecturers có created_at/updated_at theo migration đã xây

    /** CHO PHÉP GÁN HÀNG LOẠT — map đúng cột trong bảng lecturers của bạn */
    protected $fillable = [
        'user_id',           // FK -> users.id (nullable)
        'code',              // mã giảng viên (unique)
        'full_name',
        'email',             // (khuyến nghị nullable để tránh trùng users.email)
        'phone',
        'degree_id',         // FK -> degrees.id (nullable)
        'academic_rank_id',  // FK -> academic_ranks.id (nullable)
        'department_id',     // FK -> departments.id
        'active',            // tinyint(1)
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    /** QUAN HỆ */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    // public function department()
    // {
    //     return $this->belongsTo(\App\Models\Department::class, 'department_id', 'id');
    // }
    // public function degree()
    // {
    //     return $this->belongsTo(\App\Models\Degree::class, 'degree_id', 'id');
    // }
    // public function academicRank()
    // {
    //     return $this->belongsTo(\App\Models\AcademicRank::class, 'academic_rank_id', 'id');
    // }
}
