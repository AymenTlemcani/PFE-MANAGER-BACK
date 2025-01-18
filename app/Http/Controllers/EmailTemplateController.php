<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailTemplateController extends Controller
{
    private function checkAdminAccess(): void
    {
        if (!auth()->user() || auth()->user()->role !== 'Administrator') {
            abort(403, 'Only administrators can manage email templates.');
        }
    }

    public function index(): JsonResponse
    {
        $this->checkAdminAccess();
        $templates = EmailTemplate::with(['emailLogs'])->get();
        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAdminAccess();
        $validated = $request->validate([
            'name' => 'required|string|unique:email_templates',
            'subject' => 'required|string',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'placeholders' => 'array',
            'type' => 'required|in:System,Notification,Reminder',
            'language' => 'required|in:French,English',
            'is_active' => 'boolean'
        ]);

        $template = EmailTemplate::create([
            ...$validated,
            'placeholders' => $validated['placeholders'] ?? []
        ]);

        return response()->json($template, 201);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAdminAccess();
        $template = EmailTemplate::with(['emailLogs'])->findOrFail($id);
        return response()->json($template);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAdminAccess();
        $template = EmailTemplate::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string|unique:email_templates,name,' . $id . ',template_id',
            'subject' => 'string',
            'content' => 'string',
            'description' => 'nullable|string',
            'placeholders' => 'nullable|array',
            'type' => 'in:System,Notification,Reminder',
            'language' => 'in:French,English',
            'is_active' => 'boolean'
        ]);

        $template->update($validated);
        return response()->json($template);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->checkAdminAccess();
        $template = EmailTemplate::findOrFail($id);
        $template->delete();
        return response()->json(null, 204);
    }
}
