<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getUserNotifications()
    {
        $userId = Auth::id();
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'notifications' => $notifications
        ], 200);
    }

    public function markAsRead(Request $request)
    {
        $notificationId = $request->input('notification_id');
        $userId = Auth::id();
        
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();
            
        if ($notification) {
            $notification->update(['is_read' => true]);
            return response()->json(['message' => 'Notification marked as read'], 200);
        }
        
        return response()->json(['message' => 'Notification not found'], 404);
    }

    public function markAllAsRead()
    {
        $userId = Auth::id();
        
        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json(['message' => 'All notifications marked as read'], 200);
    }

    public function getUnreadCount()
    {
        $userId = Auth::id();
        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
            
        return response()->json(['count' => $count], 200);
    }

    // Helper để tạo và broadcast thông báo
    public static function createNotification($userId, $taskId, $title, $message, $type = 'task_assigned')
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'task_id' => $taskId,
            'title' => $title,
            'message' => $message,
            'type' => $type
        ]);
        
        // Broadcast sự kiện
        broadcast(new NewNotification($notification))->toOthers();
        
        return $notification;
    }
}
