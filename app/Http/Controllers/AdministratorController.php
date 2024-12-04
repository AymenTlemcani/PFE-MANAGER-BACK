<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdministratorController extends Controller
{
    public function index(): JsonResponse
    {
        $administrators = Administrator::with('user')->get();
        return response()->json($administrators);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'name' => 'required|string',
            'surname' => 'required|string'
        ]);

        $administrator = Administrator::create($validated);
        return response()->json($administrator, 201);
    }

    public function show(int $id): JsonResponse
    {
        $administrator = Administrator::with('user')->findOrFail($id);
        return response()->json($administrator);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $administrator = Administrator::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string',
            'surname' => 'string'
        ]);

        $administrator->update($validated);
        return response()->json($administrator);
    }

    public function destroy(int $id): JsonResponse
    {
        $administrator = Administrator::findOrFail($id);
        $administrator->delete();
        return response()->json(null, 204);
    }
}