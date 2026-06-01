<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;
use App\Service\Notification\NotificationType;

class NotificationFactory extends Component
{
    public function __construct(
        public string $content,
        public NotificationType $type,
        public int $id,
        public Carbon|string $createDate
    ) {
        //
    }

    public function render(): View|Closure|string
    {
        return match ($this->type) {
            NotificationType::TASK_ERROR => view('components.notification.task.error'),
            NotificationType::TASK_SUCCESS => view('components.notification.task.success')
        };
    }
}
