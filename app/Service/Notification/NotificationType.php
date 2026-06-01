<?php

namespace App\Service\Notification;

enum NotificationType: string
{
    case TASK_ERROR = 'task_error';
    case TASK_SUCCESS = 'task_success';

    static public function getAllValues(): array
    {
        return array_map(fn($value) => $value->value, NotificationType::cases());
    }
}
