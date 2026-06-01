<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class NotificationChecker
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $notifications = Notification::where('receiver_id', $user->id)
                ->where('visible', true)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $unreadCount = $notifications->where('read', false)->count();

            view()->share([
                'notifications' => $notifications
            ]);
        }

        return $next($request);
    }
}
