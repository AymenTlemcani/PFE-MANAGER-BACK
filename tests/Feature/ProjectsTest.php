<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;
use App\Models\ProjectProposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ProjectsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $responsibleTeacher;
    private User $regularTeacher;
    private User $student;
    private User $company;
    private User $student2; // For testing partnerships

    protected function setUp(): void
    {
        parent::setUp();

        // Create responsible teacher
        $this->responsibleTeacher = User::factory()->create(['role' => 'Teacher']);
        $this->responsibleTeacher->teacher()->create([
            'name' => 'John',
            'surname' => 'Doe',
            'grade' => 'PR', // Professeur
            'is_responsible' => true,
            'recruitment_date' => '2010-01-01'
        ]);

        // Create regular teacher
        $this->regularTeacher = User::factory()->create(['role' => 'Teacher']);
        $this->regularTeacher->teacher()->create([
            'name' => 'Jane',
            'surname' => 'Smith',
            'grade' => 'MAA', // Maître Assistant A
            'is_responsible' => false,
            'recruitment_date' => '2020-01-01'
        ]);

        // Create students
        $this->student = User::factory()->create(['role' => 'Student']);
        $this->student->student()->create([
            'name' => 'Bob',
            'surname' => 'Student',
            'master_option' => 'GL',
            'overall_average' => 15.5,
            'admission_year' => '2023'  // Added admission_year for student
        ]);

        $this->student2 = User::factory()->create(['role' => 'Student']);
        $this->student2->student()->create([
            'name' => 'Alice',
            'surname' => 'Partner',
            'master_option' => 'GL',
            'overall_average' => 14.5,
            'admission_year' => '2023'  // Added admission_year for student
        ]);

        // Create company
        $this->company = User::factory()->create(['role' => 'Company']);
        $this->company->company()->create([
            'company_name' => 'Tech Corp',
            'contact_name' => 'Robert',
            'contact_surname' => 'Manager',
            'industry' => 'Information Technology',
            'address' => '123 Tech Street, Silicon Valley, CA'
        ]);
    }

    public function test_teacher_can_submit_classical_project_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->regularTeacher)
            ->create([
                'type' => 'Classical',
                'status' => 'Proposed'
            ]);

        $proposalData = [
            'project_id' => $project->project_id,
            'co_supervisor_name' => 'Sarah',
            'co_supervisor_surname' => 'Expert'
        ];

        $response = $this->actingAs($this->regularTeacher)
            ->postJson('/api/project-proposals', $proposalData);

        $response->assertStatus(201);
    }

    public function test_teacher_can_submit_innovative_project_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->regularTeacher)
            ->create([
                'type' => 'Innovative',
                'status' => 'Proposed'
            ]);

        $proposalData = [
            'project_id' => $project->project_id,
            'co_supervisor_name' => 'Mike',
            'co_supervisor_surname' => 'Blockchain'
        ];

        $response = $this->actingAs($this->regularTeacher)
            ->postJson('/api/project-proposals', $proposalData);

        $response->assertStatus(201);
    }

    public function test_student_can_submit_startup_project_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create([
                'type' => 'StartUp',
                'status' => 'Proposed'
            ]);

        $proposalData = [
            'project_id' => $project->project_id,
            'partner_id' => $this->student2->student->student_id
        ];

        $response = $this->actingAs($this->student)
            ->postJson('/api/project-proposals', $proposalData);

        $response->assertStatus(201);
    }

    public function test_company_can_submit_internship_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->company)
            ->create([
                'type' => 'Internship',
                'status' => 'Proposed'
            ]);

        $proposalData = [
            'project_id' => $project->project_id,
            'internship_details' => [
                'duration' => 6,
                'location' => 'Tech Park',
                'salary' => 2000.00
            ]
        ];

        $response = $this->actingAs($this->company)
            ->postJson('/api/project-proposals', $proposalData);

        $response->assertStatus(201);
    }

    public function test_responsible_teacher_can_approve_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'proposal_status' => 'Pending',
                'proposer_type' => 'Student'
            ]);

        $response = $this->actingAs($this->responsibleTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => 'Approved',
                'review_comments' => 'Excellent proposal'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', [
            'title' => $proposal->project->title,
            'status' => 'Validated'
        ]);
    }

    public function test_regular_teacher_cannot_approve_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'proposal_status' => 'Pending'
            ]);

        $response = $this->actingAs($this->regularTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => 'Approved'
            ]);

        $response->assertStatus(403);
    }

    public function test_student_can_mark_proposal_as_final_version()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $proposals = [];
        for ($i = 0; $i < 3; $i++) {
            $proposals[] = ProjectProposal::factory()
                ->forUser($this->student)
                ->forProject($project)
                ->create([
                    'proposal_status' => 'Pending',
                    'proposer_type' => 'Student'
                ]);
        }

        $response = $this->actingAs($this->student)
            ->putJson("/api/project-proposals/{$proposals[1]->proposal_id}", [
                'is_final_version' => true
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_proposals', [
            'proposal_id' => $proposals[1]->proposal_id,
            'is_final_version' => true
        ]);

        // Check other proposals are not final
        $this->assertDatabaseHas('project_proposals', [
            'proposal_id' => $proposals[0]->proposal_id,
            'is_final_version' => false
        ]);
        $this->assertDatabaseHas('project_proposals', [
            'proposal_id' => $proposals[2]->proposal_id,
            'is_final_version' => false
        ]);
    }

    public function test_project_status_updates_after_approval()
    {
        $project = Project::factory()
            ->submittedBy($this->regularTeacher)
            ->create();

        $proposal = ProjectProposal::factory()
            ->forUser($this->regularTeacher)
            ->forProject($project)
            ->create([
                'proposal_status' => 'Pending',
                'proposer_type' => 'Teacher'
            ]);

        $this->actingAs($this->responsibleTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => 'Approved'
            ]);

        $this->assertEquals('Validated', $project->fresh()->status);
    }

    public function test_cannot_modify_project_after_validation()
    {
        $project = Project::factory()
            ->submittedBy($this->regularTeacher)
            ->create([
                'status' => 'Validated'
            ]);

        $response = $this->actingAs($this->regularTeacher)
            ->putJson("/api/projects/{$project->project_id}", [
                'title' => 'Modified Title'
            ]);

        $response->assertStatus(403);
    }

    public function test_rejected_proposal_allows_new_submission()
    {
        // Create initial project and proposal
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create([
                'type' => 'StartUp',
                'status' => 'Proposed'
            ]);

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'proposal_status' => 'Pending',
                'proposer_type' => 'Student'
            ]);

        // Reject the proposal
        $this->actingAs($this->responsibleTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => 'Rejected',
                'review_comments' => 'Needs improvement'
            ]);

        // Create and submit new project and proposal
        $newProject = Project::factory()
            ->submittedBy($this->student)
            ->create([
                'type' => 'StartUp',
                'status' => 'Proposed'
            ]);

        $response = $this->actingAs($this->student)
            ->postJson('/api/project-proposals', [
                'project_id' => $newProject->project_id,
                'partner_id' => $this->student2->student->student_id
            ]);

        $response->assertStatus(201);
    }

    public function test_student_can_submit_three_proposals_maximum()
    {
        // Create 3 projects and submit proposals
        for ($i = 0; $i < 3; $i++) {
            $project = Project::factory()
                ->submittedBy($this->student)
                ->create(['type' => 'StartUp']);

            $response = $this->actingAs($this->student)
                ->postJson('/api/project-proposals', [
                    'project_id' => $project->project_id,
                    'partner_id' => $this->student2->student->student_id
                ]);

            $response->assertStatus(201);
        }

        // Try to submit a fourth proposal
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create(['type' => 'StartUp']);

        $response = $this->actingAs($this->student)
            ->postJson('/api/project-proposals', [
                'project_id' => $project->project_id,
                'partner_id' => $this->student2->student->student_id
            ]);

        $response->assertStatus(422);
    }

    public function test_student_cannot_partner_with_self()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create(['type' => 'StartUp']);

        $response = $this->actingAs($this->student)
            ->postJson('/api/project-proposals', [
                'project_id' => $project->project_id,
                'partner_id' => $this->student->student->student_id
            ]);

        $response->assertStatus(422);
    }

    public function test_teacher_can_only_submit_classical_or_innovative_projects()
    {
        $project = Project::factory()
            ->submittedBy($this->regularTeacher)
            ->create(['type' => 'StartUp']); // Invalid type for teacher

        $response = $this->actingAs($this->regularTeacher)
            ->postJson('/api/project-proposals', [
                'project_id' => $project->project_id,
                'co_supervisor_name' => 'Sarah',
                'co_supervisor_surname' => 'Expert'
            ]);

        $response->assertStatus(422);
    }

    public function test_company_internship_proposal_requires_duration_and_location()
    {
        $project = Project::factory()
            ->submittedBy($this->company)
            ->create(['type' => 'Internship']);

        $response = $this->actingAs($this->company)
            ->postJson('/api/project-proposals', [
                'project_id' => $project->project_id,
                'internship_details' => [
                    'salary' => 2000.00
                    // Missing required duration and location
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['internship_details.duration', 'internship_details.location']);
    }

    public function test_proposal_can_be_modified_before_deadline()
    {
        $project = Project::factory()
            ->submittedBy($this->regularTeacher)
            ->create(['type' => 'Classical']);

        $proposal = ProjectProposal::factory()
            ->forUser($this->regularTeacher)
            ->forProject($project)
            ->create([
                'co_supervisor_name' => 'Old Name',
                'co_supervisor_surname' => 'Old Surname'
            ]);

        $response = $this->actingAs($this->regularTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'co_supervisor_name' => 'New Name',
                'co_supervisor_surname' => 'New Surname'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_proposals', [
            'proposal_id' => $proposal->proposal_id,
            'co_supervisor_name' => 'New Name',
            'co_supervisor_surname' => 'New Surname'
        ]);
    }
}
