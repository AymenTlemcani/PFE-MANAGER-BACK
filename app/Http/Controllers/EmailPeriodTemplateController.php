<?php

namespace App\Http\Controllers;

use App\Models\EmailPeriod;
use App\Models\EmailPeriodTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailPeriodTemplateController extends Controller
{
    public function index(int $periodId): JsonResponse
    {
        $templates = EmailPeriodTemplate::where('period_id', $periodId)->get();
        return response()->json($templates);
    }

    public function store(Request $request, int $periodId): JsonResponse
    {
        EmailPeriod::findOrFail($periodId);
        
        $validated = $request->validate([
            'template_type' => 'required|in:Initial,Reminder,Closing',
            'template_content' => 'required|string',
            'subject' => 'required|string',
            'language' => 'required|in:French,English'
        ]);

        $template = EmailPeriodTemplate::create([
            ...$validated,
            'period_id' => $periodId
        ]);

        return response()->json($template, 201);
    }

    public function update(Request $request, int $periodId, int $templateId): JsonResponse
    {
        $template = EmailPeriodTemplate::where('period_id', $periodId)
            ->where('template_id', $templateId)
            ->firstOrFail();
        
        $validated = $request->validate([
            'template_type' => 'in:Initial,Reminder,Closing',
            'template_content' => 'string',
            'subject' => 'string',
            'language' => 'in:French,English'
        ]);

        $template->update($validated);
        return response()->json($template);
    }

    public function destroy(int $periodId, int $templateId): JsonResponse
    {
        $template = EmailPeriodTemplate::where('period_id', $periodId)
            ->where('template_id', $templateId)
            ->firstOrFail();
            
        $template->delete();
        return response()->json(null, 204);
    }
}