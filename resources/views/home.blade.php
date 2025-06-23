<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Задачи</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/home.css'])
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="#" class="logo">
                    <i class="fas fa-code"></i>
                    <span>code master</span>
                </a>
                <ul class="nav-links">
                    <li><a href="#" class="active"><i class="fas fa-list-ul"></i> Задачи</a></li>
                    @if ($user && $user->is_admin)
                        <li><a href="{{ route('admin.main.show') }}"><i class="fas fa-list-ul"></i> Админ панель</a></li>
                    @endif
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
                                <div class="notification-item {{ $notification->visible ? 'unread' : '' }} {{ strtolower($notification->type->value) }}"
                                    data-notification-id="{{ $notification->id }}">
                                    <div class="notification-content">
                                        <div class="notification-icon">
                                            @if($notification->type->value === 'Error')
                                                <i class="fas fa-exclamation-circle"></i>
                                            @elseif($notification->type->value === 'Success')
                                                <i class="fas fa-check-circle"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div>{{ $notification->message }}</div>
                                            <div class="notification-time">
                                                <i class="far fa-clock"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="no-notifications">Нет новых уведомлений</div>
                            @endforelse
                        </div>
                    </div>
                @endif

                <div class="user-profile">
                    <a href="{{ $user ? route('user.profile', ['userId' => $user->id]) : route('login') }}"
                        style="text-decoration: none;">
                        <span class="username">{{ $user->name ?? 'Войти' }}</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="sidebar">
            <div class="sidebar-section">
                <h3>Сложность</h3>
                <ul>
                    <li class="active">Все</li>
                    <li><span class="difficulty-dot easy"></span> Легкие</li>
                    <li><span class="difficulty-dot medium"></span> Средние</li>
                    <li><span class="difficulty-dot hard"></span> Сложные</li>
                </ul>
            </div>
            <div class="sidebar-section">
                <h3>Статус</h3>
                <ul>
                    <li class="active">Все</li>
                    <li><i class="fas fa-check-circle solved"></i> Решенные</li>
                    <li><i class="fas fa-plus-circle attempted"></i> Пробовал</li>
                    <li><i class="fas fa-question-circle unsolved"></i> Не решенные</li>
                </ul>
            </div>
        </div>

        <div class="content-area">
            <div class="problems-header">
                <div class="problems-filter">
                    <div class="filter-group">
                        <i class="fas fa-layer-group"></i>
                        <select class="filter-select">
                            <option>Все сложности</option>
                            <option>Легкие</option>
                            <option>Средние</option>
                            <option>Сложные</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <i class="fas fa-tag"></i>
                        <select class="filter-select">
                            <option>Все статусы</option>
                            <option>Решенные</option>
                            <option>Пробовал</option>
                            <option>Не решенные</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Поиск задач...">
                    </div>
                </div>
                <div class="problems-actions">
                    <button class="btn outline-btn">
                        <i class="fas fa-sliders-h"></i> Фильтры
                    </button>
                    <button class="btn primary-btn">
                        <i class="fas fa-random"></i> Случайная задача
                    </button>
                </div>
            </div>

            <div class="problems-table">
                <div class="table-header">
                    <div class="header-item status">Статус</div>
                    <div class="header-item id">ID</div>
                    <div class="header-item title">Название</div>
                    <div class="header-item difficulty">Сложность</div>
                </div>

                @foreach ($tasks as $task)
                    <div class="table-row solved">
                        <div class="row-item status">
                        </div>
                        <div class="row-item id">{{ $task->id }}</div>
                        <div class="row-item title">
                            <a href="{{ route('task.solution', ['taskId' => $task->id]) }}">{{ $task->title }}</a>
                        </div>
                        <div
                            class="row-item difficulty {{ $task->rating < 1200 ? 'easy' : (($task->rating) < 1800 ? 'medium' : 'hard') }}">
                            {{ $task->rating }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pagination-container">
                <div class="pagination-wrapper">
                    {{ $tasks->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <script>
        window.userId = {{ json_encode(optional($user)->id) ?? null }};
    </script>

    @vite(['resources/js/home.js', 'resources/js/service/NotificationService.js', 'resources/js/echo.js'])
</body>

</html>
