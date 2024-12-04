<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $primaryKey = 'teacher_id';

    protected $fillable = [
        'user_id',
        'name',
        'surname',
        'recruitment_date',
        'grade',
        'is_responsible',
        'research_domain'
    ];

    protected $casts = [
        'recruitment_date' => 'date',
        'is_responsible' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}