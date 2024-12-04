<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::with('user')->get();
        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'company_name' => 'required|string',
            'contact_name' => 'required|string',
            'contact_surname' => 'required|string',
            'industry' => 'required|string',
            'address' => 'required|string'
        ]);

        $company = Company::create($validated);
        return response()->json($company, 201);
    }

    public function show(int $id): JsonResponse
    {
        $company = Company::with('user')->findOrFail($id);
        return response()->json($company);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $company = Company::findOrFail($id);
        
        $validated = $request->validate([
            'company_name' => 'string',
            'contact_name' => 'string',
            'contact_surname' => 'string',
            'industry' => 'string',
            'address' => 'string'
        ]);

        $company->update($validated);
        return response()->json($company);
    }

    public function destroy(int $id): JsonResponse
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return response()->json(null, 204);
    }

    public function proposeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'summary' => 'required|string',
            'technologies' => 'required|string',
            'material_needs' => 'nullable|string',
            'option' => 'required|in:GL,IA,RSD,SIC',
            'type' => 'required|in:Classical,Innovative,StartUp,Patent'
        ]);

        $project = Project::create([
            ...$validated,
            'status' => 'Proposed',
            'submitted_by' => auth()->id(),
            'submission_date' => now()
        ]);

        return response()->json($project, 201);
    }

    public function getProposedProjects(): JsonResponse
    {
        $projects = Project::where('submitted_by', auth()->id())
                          ->with(['proposal', 'assignment'])
                          ->get();
                          
        return response()->json($projects);
    }
}