<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuryPreference extends Model
{
    use HasFactory;

    protected $primaryKey = 'preference_id';

    protected $fillable = [
        'teacher_id',
        'project_id',
        'preference_order',
        'preference_date',
    ];

    protected $casts = [
        'preference_date' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}