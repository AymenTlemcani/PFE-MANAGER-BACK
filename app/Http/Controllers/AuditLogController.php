<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        // Apply filters if provided
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }
        if ($request->has('table_name')) {
            $query->where('table_name', $request->table_name);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->orderBy('timestamp', 'desc')->paginate(20);
        return response()->json($logs);
    }

    public function show(int $id): JsonResponse
    {
        $log = AuditLog::with('user')->findOrFail($id);
        return response()->json($log);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'action' => 'required|in:Create,Update,Delete',
            'table_name' => 'required|string',
            'record_id' => 'required|integer',
            'old_value' => 'nullable|string',
            'new_value' => 'nullable|string'
        ]);

        $log = AuditLog::create([
            ...$validated,
            'timestamp' => now()
        ]);

        return response()->json($log, 201);
    }
}