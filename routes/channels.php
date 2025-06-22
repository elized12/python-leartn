<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.task.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
