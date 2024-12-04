<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $primaryKey = 'project_id';

    protected $fillable = [
        'title',
        'summary',
        'technologies',
        'material_needs',
        'type',
        'option',
        'status',
        'submitted_by',
        'submission_date',
        'last_updated_date'
    ];

    protected $casts = [
        'submission_date' => 'datetime',
        'last_updated_date' => 'datetime'
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function proposal()
    {
        return $this->hasOne(ProjectProposal::class, 'project_id');
    }

    public function assignment()
    {
        return $this->hasOne(ProjectAssignment::class, 'project_id');
    }

    public function juryAssignment()
    {
        return $this->hasOne(JuryAssignment::class, 'project_id');
    }

    public function defenseSession()
    {
        return $this->hasOne(DefenseSession::class, 'project_id');
    }
}