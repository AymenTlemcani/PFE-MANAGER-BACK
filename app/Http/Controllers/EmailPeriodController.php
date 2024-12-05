<?php

namespace App\Http\Controllers;

use App\Models\EmailPeriod;
use App\Models\EmailPeriodTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailPeriodController extends Controller
{
    public function index(): JsonResponse
    {
        $periods = EmailPeriod::with(['reminders', 'templates'])->get();
        return response()->json($periods);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_name' => 'required|string|unique:email_periods',
            'target_audience' => 'required|in:Students,Teachers,Companies,Administrators,All',
            'start_date' => 'required|date',
            'closing_date' => 'required|date|after:start_date',
            'status' => 'required|in:Draft,Active,Closed,Cancelled'
        ]);

        $period = EmailPeriod::create($validated);
        return response()->json($period, 201);
    }

    public function show(int $id): JsonResponse
    {
        $period = EmailPeriod::with(['reminders', 'templates'])->findOrFail($id);
        return response()->json($period);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $period = EmailPeriod::findOrFail($id);
        
        $validated = $request->validate([
            'period_name' => 'string|unique:email_periods,period_name,' . $id . ',period_id',
            'target_audience' => 'in:Students,Teachers,Companies,Administrators,All',
            'start_date' => 'date',
            'closing_date' => 'date|after:start_date',
            'status' => 'in:Draft,Active,Closed,Cancelled'
        ]);

        $period->update($validated);
        return response()->json($period);
    }

    public function destroy(int $id): JsonResponse
    {
        $period = EmailPeriod::findOrFail($id);
        $period->delete();
        return response()->json(null, 204);
    }

    public function activate(int $id): JsonResponse
    {
        $period = EmailPeriod::findOrFail($id);
        $period->update(['status' => 'Active']);
        return response()->json(['message' => 'Email period activated']);
    }

    public function close(int $id): JsonResponse
    {
        $period = EmailPeriod::findOrFail($id);
        $period->update(['status' => 'Closed']);
        return response()->json(['message' => 'Email period closed']);
    }
}