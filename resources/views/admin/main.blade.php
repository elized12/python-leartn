@extends('layout.admin')

@section('title', 'Главная')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">
            <button type="button" class="close-alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <section class="admin-hero">
        <div>
            <span class="eyebrow">Live dashboard</span>
            <h2>Пульс платформы</h2>
            <p>Новые пользователи, задачи и попытки решений появляются здесь без перезагрузки страницы.</p>
        </div>
        <div class="live-status" id="admin-live-status">
            <span></span>
            Подключаемся
        </div>
    </section>

    <div class="stats-grid dashboard-stats">
        <article class="stat-card accent-blue">
            <span class="stat-label">Пользователи</span>
            <strong data-stat="users">{{ $usersCount ?? 0 }}</strong>
            <small>зарегистрировано всего</small>
        </article>
        <article class="stat-card accent-green">
            <span class="stat-label">Подтверждённые</span>
            <strong data-stat="verified_users">{{ $verifiedUsersCount ?? 0 }}</strong>
            <small>подтвердили email</small>
        </article>
        <article class="stat-card accent-green">
            <span class="stat-label">Задачи</span>
            <strong data-stat="tasks">{{ $tasksCount ?? 0 }}</strong>
            <small>доступно в системе</small>
        </article>
        <article class="stat-card accent-violet">
            <span class="stat-label">Попытки сегодня</span>
            <strong data-stat="attempts_today">{{ $attemptsTodayCount ?? 0 }}</strong>
            <small>отправлено решений</small>
        </article>
        <article class="stat-card accent-amber">
            <span class="stat-label">Accepted</span>
            <strong data-stat="completed_tasks">{{ $completedTasksCount ?? 0 }}</strong>
            <small>успешных решений</small>
        </article>
        <article class="stat-card accent-blue">
            <span class="stat-label">Курсы</span>
            <strong data-stat="courses">{{ $coursesCount ?? 0 }}</strong>
            <small>создано всего</small>
        </article>
        <article class="stat-card accent-violet">
            <span class="stat-label">Начатые курсы</span>
            <strong data-stat="started_courses">{{ $startedCoursesCount ?? 0 }}</strong>
            <small>всего записей на курсы</small>
        </article>
        <article class="stat-card accent-green">
            <span class="stat-label">Пройденные курсы</span>
            <strong data-stat="completed_courses">{{ $completedCoursesCount ?? 0 }}</strong>
            <small>курсов завершено учениками</small>
        </article>
    </div>

    <div class="dashboard-grid">
        <section class="admin-card live-feed-card">
            <div class="card-heading">
                <div>
                    <span class="eyebrow">Realtime</span>
                    <h2>Последняя активность</h2>
                </div>
                <span class="feed-counter" id="feed-counter">{{ $latestActivity->count() }}</span>
            </div>

            <div class="activity-feed" id="admin-activity-feed">
                @forelse($latestActivity as $activity)
                    <article class="activity-item activity-{{ $activity['type'] }}">
                        <span class="activity-dot"></span>
                        <div>
                            <strong>{{ $activity['title'] }}</strong>
                            <p>{{ $activity['message'] }}</p>
                            <time>{{ $activity['created_at']->format('d.m.Y H:i') }}</time>
                        </div>
                    </article>
                @empty
                    <p class="empty-state">Пока активности нет. Как только кто-то зарегистрируется или отправит решение, запись появится здесь.</p>
                @endforelse
            </div>
        </section>

        <aside class="admin-card command-card">
            <div class="card-heading">
                <div>
                    <span class="eyebrow">Управление</span>
                    <h2>Быстрые действия</h2>
                </div>
            </div>

            <div class="quick-actions">
                <a href="{{ route('admin.task-create.show') }}" class="quick-action">
                    <span>+</span>
                    Создать задачу
                </a>
                <a href="{{ route('admin.categories.index') }}" class="quick-action">
                    <span>#</span>
                    Категории
                </a>
                <a href="{{ route('admin.environments.index') }}" class="quick-action">
                    <span>{ }</span>
                    Окружения
                </a>
            </div>
        </aside>
    </div>

    <section class="admin-card course-starts-card">
        <div class="card-heading">
            <div>
                <span class="eyebrow">Courses</span>
                <h2>Старт и завершение курсов</h2>
            </div>
        </div>

        <div class="course-starts-list">
            @forelse($courseStarts as $courseStat)
                <article class="course-start-row">
                    <div class="course-start-title">
                        <strong>{{ $courseStat['title'] }}</strong>
                        <span>{{ $courseStat['lessons_count'] }} уроков</span>
                    </div>
                    <div class="course-start-metric">
                        <strong>{{ $courseStat['started_count'] }}</strong>
                        <span>начали</span>
                    </div>
                    <div class="course-start-metric is-completed">
                        <strong>{{ $courseStat['completed_count'] }}</strong>
                        <span>прошли</span>
                    </div>
                </article>
            @empty
                <p class="empty-state">Курсы пока никто не начинал.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('js')
    @vite(['resources/js/echo.js', 'resources/js/admin/dashboard.js'])
@endpush
