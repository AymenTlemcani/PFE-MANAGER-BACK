<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\EmailPeriod;

class CheckEmailPeriodDeadline
{
    public function handle(Request $request, Closure $next, string $periodName)
    {
        $period = EmailPeriod::where('period_name', $periodName)
            ->where('status', 'Active')
            ->first();

        if (!$period) {
            return response()->json(['message' => 'This submission period is not active.'], 403);
        }

        if (now() > $period->closing_date) {
            return response()->json(['message' => 'Submission deadline has passed.'], 403);
        }

        return $next($request);
    }
}