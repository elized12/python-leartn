<nav class="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <a href="#" class="logo">
                <i class="fas fa-code"></i>
                <span>Code master</span>
            </a>
            <ul class="nav-links">
                @if (Auth::user() && Auth::user()->is_admin)
                    <li><a href="{{ route('admin.main.show') }}"><i class="fas fa-cogs"></i>Админ панель</a></li>
                @endif

                <li><a href="{{ request()->routeIs('tasks.show') ? '#' : route('tasks.show') }}"
                        class="{{ request()->routeIs('tasks.show') ? 'active' : '' }}"><i class="fas fa-list-ul"></i>
                        Задачи</a></li>
                <li><a href="{{ request()->routeIs('courses.show') ? '#' : route('courses.show') }}"
                        class="{{ request()->routeIs('courses.show') ? 'active' : '' }}"><i
                            class="fas fa-graduation-cap"></i>Курсы</a></li>
                <li><a href="{{ request()->routeIs('rating.index') ? '#' : route('rating.index') }}"
                        class="{{ request()->routeIs('rating.index') ? 'active' : '' }}"><i
                            class="fas fa-ranking-star"></i>Рейтинг</a></li>
            </ul>
        </div>
        <div class="nav-right">
            @if ($notifications)
                <div class="notification-container">
                    <div class="notification-bell" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count"
                            id="notification-count-element">{{ $notifications->where('visible', true)->count() }}</span>
                    </div>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Уведомления</h4>
                            <a href="#" id="markAllAsRead">Пометить все как прочитанные</a>
                        </div>
                        @forelse($notifications as $notification)
                            <x-notification-factory :content="$notification->content" :type="$notification->type"
                                :createDate="$notification->created_at" :id="$notification->id" />
                        @empty
                            <div class="no-notifications">Нет новых уведомлений</div>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="user-profile">
                <a href="{{ Auth::user() ? route('user.profile', ['userId' => Auth::user()->id]) : route('login') }}"
                    style="text-decoration: none;">
                    <span class="username">{{ Auth::user()->name ?? 'Войти' }}</span>
                </a>
            </div>
        </div>
    </div>
</nav>

@push('js')
    @vite(['resources/js/notification/notification.js', 'resources/js/echo.js'])
    <script>
        window.userId = {{ json_encode(optional(Auth::user())->id) ?? null }};
    </script>
@endpush
