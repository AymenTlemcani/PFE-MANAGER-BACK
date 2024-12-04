<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'project_id',
        'student_id',
        'teacher_id',
        'company_id',
        'assignment_date',
        'assignment_method'
    ];

    protected $casts = [
        'assignment_date' => 'datetime'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}