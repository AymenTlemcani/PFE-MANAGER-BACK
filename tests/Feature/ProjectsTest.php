<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;  
use App\Models\ProjectProposal;
use Illuminate\Support\Facades\DB;
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

        $project = $proposal->project;  // Get reference to project

        $response = $this->actingAs($this->responsibleTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => ProjectProposal::STATUS_APPROVED,
                'comments' => 'Excellent proposal'
            ]);

        $response->assertStatus(200);
        $proposal->refresh();
        $this->assertEquals('Validated', $proposal->project->status);
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
                'proposal_status' => 'Approved',
                'comments' => 'Trying to approve'
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

        $project = $proposal->project;  // Get reference to project

        $this->actingAs($this->responsibleTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => ProjectProposal::STATUS_APPROVED,
                'comments' => 'Approved without changes'
            ]);

        $proposal->refresh();
        $this->assertEquals('Validated', $proposal->project->status);
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
                'co_supervisor_surname' => 'New Surname',
                'comments' => 'Updated supervisor details',
                'suggested_changes' => ['supervisor' => 'Changed to better match expertise']
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_proposals', [
            'proposal_id' => $proposal->proposal_id,
            'co_supervisor_name' => 'New Name',
            'co_supervisor_surname' => 'New Surname'
        ]);
    }

    public function test_proposal_cannot_be_modified_when_final()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'is_final_version' => true,
                'proposal_status' => ProjectProposal::STATUS_PENDING
            ]);

        $response = $this->actingAs($this->student)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'title' => 'Modified Title'
            ]);

        $response->assertStatus(403);
    }

    public function test_proposal_status_changes_are_atomic()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create(['proposal_status' => ProjectProposal::STATUS_PENDING]);

        DB::beginTransaction();
        
        $response = $this->actingAs($this->responsibleTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'proposal_status' => ProjectProposal::STATUS_APPROVED
            ]);

        DB::rollBack();

        $this->assertEquals(
            ProjectProposal::STATUS_PENDING,
            $proposal->fresh()->proposal_status
        );
    }

    public function test_rejected_proposal_is_not_counted_in_maximum_limit()
    {
        // Create 3 proposals with one rejected
        $proposals = [];
        for ($i = 0; $i < 3; $i++) {
            $project = Project::factory()
                ->submittedBy($this->student)
                ->create();

            $proposal = ProjectProposal::factory()
                ->forUser($this->student)
                ->forProject($project)
                ->create(['proposal_status' => ProjectProposal::STATUS_PENDING]);

            if ($i === 0) {
                $this->actingAs($this->responsibleTeacher)
                    ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                        'proposal_status' => ProjectProposal::STATUS_REJECTED,
                        'comments' => 'Rejected - needs work'
                    ]);
            }

            $proposals[] = $proposal;
        }

        // Should be able to create one more proposal since one was rejected
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $response = $this->actingAs($this->student)
            ->postJson('/api/project-proposals', [
                'project_id' => $project->project_id,
                'partner_id' => $this->student2->student->student_id
            ]);

        $response->assertStatus(201);
    }

    public function test_teacher_can_edit_student_proposal()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create(['type' => 'StartUp']);

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'proposal_status' => ProjectProposal::STATUS_PENDING,
                'co_supervisor_name' => 'Original Name'
            ]);

        $response = $this->actingAs($this->regularTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'co_supervisor_name' => 'Updated Name',
                'comments' => 'Please update the methodology',
                'suggested_changes' => ['methodology' => 'Add more detail']
            ]);

        $response->assertStatus(200);
        $this->assertEquals(ProjectProposal::STATUS_EDITED, $proposal->fresh()->proposal_status);
        $this->assertEquals($this->regularTeacher->user_id, $proposal->fresh()->edited_by);
        $this->assertNotNull($proposal->fresh()->edited_at);
        $this->assertFalse($proposal->fresh()->edit_accepted);
    }

    public function test_teacher_cannot_edit_final_version()
    {
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'is_final_version' => true,
                'proposal_status' => ProjectProposal::STATUS_PENDING
            ]);

        $response = $this->actingAs($this->regularTeacher)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'co_supervisor_name' => 'New Name',
                'comments' => 'Update needed',
                'suggested_changes' => ['content' => 'Revise']
            ]);

        $response->assertStatus(403);
    }

    public function test_student_can_accept_edited_proposal()
    {
        // Create a proposal that has been edited by a teacher
        $project = Project::factory()
            ->submittedBy($this->student)
            ->create();

        $originalVersion = [
            'co_supervisor_name' => 'Original Name',
            'co_supervisor_surname' => 'Original Surname',
            'additional_details' => ['original' => 'details']
        ];

        $proposal = ProjectProposal::factory()
            ->forUser($this->student)
            ->forProject($project)
            ->create([
                'proposal_status' => ProjectProposal::STATUS_EDITED,
                'edited_by' => $this->regularTeacher->user_id,
                'edited_at' => now(),
                'edit_accepted' => false,
                'last_edited_version' => $originalVersion,
                'co_supervisor_name' => 'Updated Name',
                'co_supervisor_surname' => 'Updated Surname'
            ]);

        // Student accepts the teacher's edits
        $response = $this->actingAs($this->student)
            ->putJson("/api/project-proposals/{$proposal->proposal_id}", [
                'accept_changes' => true,
                'comments' => 'The suggested changes improve the proposal'
            ]);

        $response->assertStatus(200);
        
        $updatedProposal = $proposal->fresh();
        $this->assertTrue($updatedProposal->edit_accepted);
        $this->assertEquals(ProjectProposal::STATUS_ACCEPTED, $updatedProposal->proposal_status);
        $this->assertEquals('Updated Name', $updatedProposal->co_supervisor_name);
        $this->assertEquals('Updated Surname', $updatedProposal->co_supervisor_surname);
    }

    public function test_responsible_teacher_can_filter_projects_by_status()
    {
        // Create multiple projects with different statuses
        Project::factory()->count(2)->create(['status' => 'Proposed']);
        Project::factory()->count(3)->create(['status' => 'Validated']);
        
        // Test filtering as responsible teacher
        $response = $this->actingAs($this->responsibleTeacher)
            ->getJson('/api/projects?status=Proposed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'projects' => [
                    '*' => [
                        'project_id',
                        'title',
                        'submitter_details',
                        'submitter_role'
                    ]
                ],
                'total'
            ])
            ->assertJson([
                'total' => 2
            ]);

        // Test that submitter details are included
        $responseData = $response->json();
        $this->assertArrayHasKey('submitter_details', $responseData['projects'][0]);
        $this->assertArrayHasKey('submitter_role', $responseData['projects'][0]);
    }

    public function test_regular_teacher_cannot_filter_projects_by_status()
    {
        Project::factory()->count(2)->create(['status' => 'Proposed']);
        
        $response = $this->actingAs($this->regularTeacher)
            ->getJson('/api/projects?status=Proposed');

        // Should return unfiltered list
        $response->assertStatus(200)
            ->assertJsonMissing(['submitter_details'])
            ->assertJsonMissing(['total']);
    }
}
