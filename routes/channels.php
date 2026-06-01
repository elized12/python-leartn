<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.notification.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('user.task.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('admin.dashboard', function ($user) {
    return (bool) $user->is_admin;
});
