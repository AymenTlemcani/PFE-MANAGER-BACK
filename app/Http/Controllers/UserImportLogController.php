<?php

namespace App\Http\Controllers;

use App\Models\UserImportLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserImportLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserImportLog::with('importedByUser');

        // Apply filters if provided
        if ($request->has('import_type')) {
            $query->where('import_type', $request->import_type);
        }
        if ($request->has('import_status')) {
            $query->where('import_status', $request->import_status);
        }

        $logs = $query->orderBy('import_date', 'desc')->paginate(20);
        return response()->json($logs);
    }

    public function show(int $id): JsonResponse
    {
        $log = UserImportLog::with('importedByUser')->findOrFail($id);
        return response()->json($log);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imported_by' => 'required|exists:users,user_id',
            'import_type' => 'required|string',
            'total_records_imported' => 'required|integer',
            'successful_imports' => 'required|integer',
            'failed_imports' => 'required|integer',
            'import_file_name' => 'required|string',
            'import_status' => 'required|string'
        ]);

        $log = UserImportLog::create([
            ...$validated,
            'import_date' => now()
        ]);

        return response()->json($log, 201);
    }

    public function getImportStatistics(): JsonResponse
    {
        $stats = [
            'total_imports' => UserImportLog::count(),
            'successful_imports' => UserImportLog::sum('successful_imports'),
            'failed_imports' => UserImportLog::sum('failed_imports'),
            'by_type' => UserImportLog::selectRaw('import_type, COUNT(*) as count')
                ->groupBy('import_type')
                ->get()
        ];

        return response()->json($stats);
    }
}