<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefenseSession extends Model
{
    use HasFactory;

    protected $primaryKey = 'session_id';

    protected $fillable = [
        'project_id',
        'room',
        'date',
        'time',
        'duration',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}