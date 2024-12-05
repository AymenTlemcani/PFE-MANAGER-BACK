<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Project;

class CheckProjectAssignment
{
    public function handle(Request $request, Closure $next)
    {
        $project = Project::find($request->route('projectId'));
        
        if ($project && $project->status === 'Assigned') {
            return response()->json(['message' => 'Project is already assigned.'], 403);
        }

        return $next($request);
    }
}