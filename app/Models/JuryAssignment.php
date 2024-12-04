<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuryAssignment extends Model
{
    use HasFactory;

    protected $primaryKey = 'jury_id';

    protected $fillable = [
        'project_id',
        'examiner_id',
        'president_id',
        'supervisor_id',
        'assignment_method',
        'assignment_date',
    ];

    protected $casts = [
        'assignment_date' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function examiner()
    {
        return $this->belongsTo(Teacher::class, 'examiner_id');
    }

    public function president()
    {
        return $this->belongsTo(Teacher::class, 'president_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Teacher::class, 'supervisor_id');
    }
}