<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;               // Spatie RBAC

/**
 * @method \Illuminate\Support\Collection getRoleNames()
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    // Map -> users (mặc định). Nếu bạn rename bảng users, chỉnh lại $table.
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    protected $appends = ['role_names'];

    /** CỘT CHO PHÉP GÁN HÀNG LOẠT */
    protected $fillable = [
        'name',        // string
        'email',       // string (unique)
        'password',    // hashed
        'must_change_password',
    ];

    /** ẨN KHI TOJSON */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** CASTING */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'must_change_password' => 'boolean',
    ];

    public function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Hash::needsRehash($value) ? Hash::make($value) : $value
        );
    }

    public function getRoleNamesAttribute()
    {
        return method_exists($this, 'getRoleNames')
            ? $this->getRoleNames()->values()
            : collect();
    }

    /** QUAN HỆ: 1-1 tới Lecturers (nếu bạn ràng buộc như thiết kế) */
    public function lecturer()
    {
        return $this->hasOne(Lecturer::class, 'user_id', 'id');
    }
}
