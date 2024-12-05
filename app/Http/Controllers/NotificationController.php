<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('sent_date', 'desc')
            ->get();
        return response()->json($notifications);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'message' => 'required|string',
            'notification_type' => 'required|in:Email,InApp',
            'related_entity_type' => 'required|string',
            'related_entity_id' => 'required|integer'
        ]);

        $notification = Notification::create([
            ...$validated,
            'sent_date' => now(),
            'is_read' => false
        ]);

        if ($validated['notification_type'] === 'Email') {
            $this->sendEmail($notification);
        }

        return response()->json($notification, 201);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('notification_id', $id)
            ->firstOrFail();
        
        $notification->update(['is_read' => true]);
        return response()->json($notification);
    }

    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function notifyPairRequest(int $requestingStudentId, int $targetStudentId): JsonResponse
    {
        $notification = Notification::create([
            'user_id' => $targetStudentId,
            'message' => 'You have received a new pair request',
            'notification_type' => 'InApp',
            'sent_date' => now(),
            'is_read' => false,
            'related_entity_type' => 'StudentPair',
            'related_entity_id' => $requestingStudentId
        ]);

        // Send email notification
        $this->sendEmail($notification);

        return response()->json($notification, 201);
    }

    public function notifyProjectAssignment(array $userIds, string $message, int $projectId): JsonResponse
    {
        $notifications = [];
        foreach ($userIds as $userId) {
            $notification = Notification::create([
                'user_id' => $userId,
                'message' => $message,
                'notification_type' => 'Email',
                'sent_date' => now(),
                'is_read' => false,
                'related_entity_type' => 'Project',
                'related_entity_id' => $projectId
            ]);

            $this->sendEmail($notification);
            $notifications[] = $notification;
        }

        return response()->json($notifications, 201);
    }

    private function sendEmail(Notification $notification): void
    {
        $user = User::find($notification->user_id);
        
        // Here you would implement the actual email sending logic
        // using Laravel's Mail facade or other email service
        // Mail::to($user->email)->send(new NotificationMail($notification));
    }
}