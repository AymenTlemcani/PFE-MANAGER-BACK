<?php

namespace App\Http\Controllers;

use App\Models\Company;
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
}