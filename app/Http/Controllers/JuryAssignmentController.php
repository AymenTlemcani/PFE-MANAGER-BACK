<?php

namespace App\Http\Controllers;

use App\Models\JuryAssignment;
use App\Models\Teacher;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class JuryAssignmentController extends Controller
{
    public function index(): JsonResponse
    {
        $assignments = JuryAssignment::with(['project', 'examiner', 'president', 'supervisor'])->get();
        return response()->json($assignments);
    }

    public function autoAssign(): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Get all projects that need jury assignment
            $projects = Project::whereDoesntHave('juryAssignment')->get();

            foreach ($projects as $project) {
                // Get supervisor
                $supervisor = Teacher::find($project->supervisor_id);
                
                // Get available teachers (excluding supervisor)
                $availableTeachers = Teacher::where('teacher_id', '!=', $supervisor->teacher_id)
                    ->orderBy('grade', 'desc')
                    ->orderBy('recruitment_date', 'asc')
                    ->get();

                if ($availableTeachers->count() < 2) {
                    throw new \Exception("Not enough teachers available for project {$project->project_id}");
                }

                // Assign president (highest grade and most senior)
                $president = $availableTeachers->shift();
                
                // Assign examiner (next available teacher)
                $examiner = $availableTeachers->shift();

                // Create jury assignment
                JuryAssignment::create([
                    'project_id' => $project->project_id,
                    'examiner_id' => $examiner->teacher_id,
                    'president_id' => $president->teacher_id,
                    'supervisor_id' => $supervisor->teacher_id,
                    'assignment_method' => 'Automatic',
                    'assignment_date' => now()
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Jury assignments completed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getProjectJury(int $projectId): JsonResponse
    {
        $jury = JuryAssignment::with(['examiner', 'president', 'supervisor'])
            ->where('project_id', $projectId)
            ->firstOrFail();

        return response()->json($jury);
    }
}