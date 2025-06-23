<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Профиль | CodeMaster</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/user/profile.css'])
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="{{ route('home') }}" class="logo">
                <i class="fas fa-code"></i>
                <span>code master</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-button">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </button>
            </form>
        </div>
    </nav>

    <div class="main-container">
        <div class="profile-header">
            <div class="profile-info">
                <h1>nick</h1>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value">{{ $tasksCompletedCount }}</div>
                        <div>Решено задач</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="solved-problems">
            <h2 class="section-title">
                <i class="fas fa-check-circle"></i> Решенные задачи
            </h2>
            <div class="problems-list">
                @foreach ($tasksCompleted as $task)
                    <div class="problem-card">
                        <a href="{{route('task.solution', ['taskId' => $task->id]) }}"
                            class="problem-title">{{ $task->title }}</a>
                        @php
                            if ($task->rating < 1200) {
                                $levelClass = 'easy';
                                $messageLevel = 'Легкий';
                            } elseif ($task->rating < 1800) {
                                $levelClass = 'medium';
                                $messageLevel = 'Средний';
                            } else {
                                $levelClass = 'hard';
                                $messageLevel = 'Сложный';
                            }
                        @endphp
                        <div class="problem-difficulty {{ $levelClass }}">{{ $messageLevel }}</div>
                        <div class="problem-date">Решено {{ $task->create_at }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</body>

</html>
