<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProposal extends Model
{
    use HasFactory;

    protected $primaryKey = 'proposal_id';

    protected $fillable = [
        'project_id',
        'submitted_by',
        'co_supervisor_name',
        'co_supervisor_surname',
        'proposal_status',
        'review_comments'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}