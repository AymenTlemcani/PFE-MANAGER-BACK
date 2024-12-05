<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\StudentPair;

class CheckPairStatus
{
    public function handle(Request $request, Closure $next)
    {
        $student = auth()->user()->student;
        
        $existingPair = StudentPair::where(function($query) use ($student) {
            $query->where('student1_id', $student->student_id)
                  ->orWhere('student2_id', $student->student_id);
        })->where('status', 'Accepted')
          ->first();

        if ($existingPair) {
            return response()->json(['message' => 'You already have an accepted pair.'], 403);
        }

        return $next($request);
    }
}