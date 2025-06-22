<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function hiddenNotification(int $notificationId): JsonResponse
    {
        $notification = Notification::find($notificationId);
        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'notification not found'
            ], 404);
        }

        $user = auth()->user();
        if ($user->id != $notification->receiver_id) {
            return response()->json([
                'status' => false,
                'message' => 'not user'
            ], 400);
        }

        $notification->visible = false;
        $notification->save();

        return response()->json([
            'status' => true,
            'message' => 'notification hidden'
        ]);
    }
}
