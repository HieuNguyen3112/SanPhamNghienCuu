<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class AcademicRank extends Model
{
    protected $table = 'academic_ranks';
    public $timestamps = true;
    protected $fillable = ['code', 'name'];
}
