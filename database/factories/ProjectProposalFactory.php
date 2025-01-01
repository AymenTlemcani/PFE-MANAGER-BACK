<?php

namespace Database\Factories;

use App\Models\ProjectProposal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectProposalFactory extends Factory
{
    protected $model = ProjectProposal::class;

    public function definition(): array
    {
        return [
            'co_supervisor_name' => null,  // Make nullable by default
            'co_supervisor_surname' => null,  // Make nullable by default
            'proposal_status' => 'Pending',
            'review_comments' => null,
            'proposal_order' => 1,
            'proposer_type' => 'Teacher',
            'is_final_version' => false
        ];
    }

    public function forUser($user)
    {
        return $this->state(fn (array $attributes) => [
            'submitted_by' => $user->user_id,  // Fix: Use user_id instead of id
            'proposer_type' => $user->role
        ]);
    }

    public function forProject($project)
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->project_id
        ]);
    }
}