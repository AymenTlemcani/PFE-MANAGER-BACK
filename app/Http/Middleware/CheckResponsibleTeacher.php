<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckResponsibleTeacher
{
    public function handle(Request $request, Closure $next)
    {
        $teacher = auth()->user()->teacher;
        
        if (!$teacher || !$teacher->is_responsible) {
            return response()->json(['message' => 'Only responsible teachers can perform this action.'], 403);
        }

        return $next($request);
    }
}