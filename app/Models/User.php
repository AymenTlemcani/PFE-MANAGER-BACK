<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';
    public $timestamps = true;  // Add this line

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'temporary_password',  // Change from temp_password to temporary_password
        'temporary_password_expiration',  // Add this field
        'role',
        'is_active',
        'must_change_password',
        'language_preference',
        'profile_picture_url',
        'date_of_birth'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'temporary_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'temporary_password_expiration' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login' => 'datetime',
        'profile_picture_url' => 'string'  // Add this line
    ];

    public function administrator()
    {
        return $this->hasOne(Administrator::class, 'user_id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'user_id');
    }
}
