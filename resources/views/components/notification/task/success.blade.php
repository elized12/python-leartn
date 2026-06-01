<div class="notification-item unread {{ strtolower($type->value) }}" data-notification-id="{{ $id }}">
    <div class="notification-content">
        <div class="notification-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div>{!! $content !!}</div>
            <div class="notification-time">
                <i class="far fa-clock"></i>
                {{ $createDate->diffForHumans() }}
            </div>
        </div>
    </div>
</div>