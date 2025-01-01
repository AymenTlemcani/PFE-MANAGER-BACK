<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProposal extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'Pending';
    const STATUS_APPROVED = 'Approved';
    const STATUS_REJECTED = 'Rejected';
    const STATUS_EDITED = 'Edited';
    const STATUS_ACCEPTED = 'Accepted';
    const MAX_STUDENT_PROPOSALS = 3;

    protected $primaryKey = 'proposal_id';

    protected $fillable = [
        'project_id',
        'submitted_by',
        'co_supervisor_name',
        'co_supervisor_surname',
        'proposal_status',
        'review_comments',
        'proposal_order',
        'proposer_type',
        'additional_details',
        'is_final_version',
        'edited_by',
        'edited_at',
        'edit_accepted',
        'last_edited_version'
    ];

    protected $casts = [
        'additional_details' => 'array',
        'last_edited_version' => 'array',
        'is_final_version' => 'boolean',
        'edited_at' => 'datetime',
        'edit_accepted' => 'boolean'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id')->withoutGlobalScopes();
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function isPending(): bool
    {
        return $this->proposal_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->proposal_status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->proposal_status === self::STATUS_REJECTED;
    }

    public function canBeModified(): bool
    {
        // Allow modifications if pending or edited (but not final)
        return ($this->isPending() || $this->needsStudentReview()) && !$this->is_final_version;
    }

    public function canBeEditedBy(User $user): bool
    {
        return $user->role === 'Teacher' && 
               ($this->isPending() || $this->needsStudentReview()) && 
               !$this->isRejected() &&
               !$this->is_final_version;
    }

    public function needsStudentReview(): bool
    {
        return $this->proposal_status === self::STATUS_EDITED && 
               !$this->edit_accepted;
    }

    public function acceptEdit(): void
    {
        $this->edit_accepted = true;
        $this->proposal_status = self::STATUS_ACCEPTED;
        $this->is_final_version = true;
        $this->save();
    }

    public static function getActiveProposalsCount(int $userId): int
    {
        return self::where('submitted_by', $userId)
            ->whereNotIn('proposal_status', [self::STATUS_REJECTED])
            ->count();
    }
}