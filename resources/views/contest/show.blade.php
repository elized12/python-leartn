@extends('layout.main')

@section('content')
    <div class="contest-page">
        @if(session('success'))
            <div class="contest-alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="contest-alert error">{{ session('error') }}</div>
        @endif

        <section class="contest-hero {{ !$isStarted ? 'contest-hero-waiting' : '' }}">
            <div>
                <span class="contest-status">{{ $contest->statusLabel() }}</span>
                <h1>{{ $contest->title }}</h1>
                <p>{{ $contest->description ?: 'Описания пока нет.' }}</p>
                <div class="contest-hero-meta">
                    <span>Старт: {{ $contest->starts_at?->format('d.m.Y H:i') ?? '—' }}</span>
                    <span>Финиш: {{ $contest->ends_at?->format('d.m.Y H:i') ?? '—' }}</span>
                    <span>Длительность: {{ $contest->duration_minutes }} мин.</span>
                    <span>Попытки онлайн: {{ $onlineAttempts }}</span>
                </div>
            </div>

            @auth
                @if(!$isParticipant)
                    <form method="POST" action="{{ route('contests.join', $contest) }}">
                        @csrf
                        <button class="contest-primary-button" type="submit">Присоединиться</button>
                    </form>
                @else
                    <span class="contest-joined">Вы участвуете</span>
                @endif
            @else
                <a class="contest-primary-button" href="{{ route('login') }}">Войти для участия</a>
            @endauth
        </section>

        @if(!$isStarted)
            <section class="contest-countdown-panel" data-contest-countdown data-start-at="{{ $startsAtIso }}">
                <div class="contest-countdown-copy">
                    <span class="contest-countdown-eyebrow">Задачи откроются одновременно для всех</span>
                    <h2>До начала контеста</h2>
                    <p>Названия, рейтинги и условия задач скрыты до старта. Когда время придёт, страница обновится сама.</p>
                </div>
                <div class="contest-countdown-clock" aria-live="polite">
                    <div>
                        <strong data-countdown-days>00</strong>
                        <span>дней</span>
                    </div>
                    <div>
                        <strong data-countdown-hours>00</strong>
                        <span>часов</span>
                    </div>
                    <div>
                        <strong data-countdown-minutes>00</strong>
                        <span>минут</span>
                    </div>
                    <div>
                        <strong data-countdown-seconds>00</strong>
                        <span>секунд</span>
                    </div>
                </div>
            </section>
        @endif

        <div class="contest-layout">
            <section class="contest-panel">
                <div class="contest-panel-header">
                    <h2>Задачи</h2>
                    <span>{{ $isStarted ? $tasks->count() : 'скрыты' }}</span>
                </div>
                @if(!$isStarted)
                    <div class="contest-locked-tasks">
                        <div class="contest-lock-icon"><i class="fas fa-lock"></i></div>
                        <h3>Набор задач закрыт до старта</h3>
                        <p>Так никто не получит преимущество заранее. После начала здесь появятся задачи и кнопки решения.</p>
                        <div class="contest-locked-preview">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                @else
                    <div class="contest-task-list">
                        @forelse($tasks as $task)
                            <div class="contest-task-row">
                                <div>
                                    <h3>{{ $task->title }}</h3>
                                    <span>Рейтинг {{ $task->rating }}</span>
                                </div>
                                @if($isRunning && $isParticipant)
                                    <a href="{{ route('contests.task', ['contest' => $contest, 'task' => $task]) }}">Решать</a>
                                @else
                                    <span class="contest-task-locked">{{ $isRunning ? 'Нужно присоединиться' : 'Недоступно' }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="contest-muted">Задачи пока не выбраны.</p>
                        @endforelse
                    </div>
                @endif
            </section>

            <aside class="contest-side">
                <section class="contest-panel">
                    <div class="contest-panel-header">
                        <h2>{{ $contest->isFinished() ? 'Результаты' : 'Лидеры' }}</h2>
                    </div>
                    @if(!$isStarted)
                        <p class="contest-muted">Таблица лидеров появится после старта.</p>
                    @else
                        @forelse($leaderboard as $row)
                            <div class="contest-leader-row {{ $loop->first ? 'winner' : '' }}">
                                <strong>{{ $row->rank }}. {{ $row->name }}</strong>
                                <span>{{ $row->solved }} решено</span>
                            </div>
                        @empty
                            <p class="contest-muted">Пока нет принятых решений.</p>
                        @endforelse
                    @endif
                </section>

                <section class="contest-panel">
                    <div class="contest-panel-header">
                        <h2>Последние попытки</h2>
                    </div>
                    @if(!$isStarted)
                        <p class="contest-muted">Лента попыток откроется вместе с задачами.</p>
                    @else
                        @forelse($recentAttempts as $attempt)
                            <div class="contest-attempt-row {{ $attempt->status === \App\Service\Task\TaskStatus::COMPLETED ? 'accepted' : '' }}">
                                <strong>{{ $attempt->user?->name ?? 'Пользователь' }}</strong>
                                <span>{{ $attempt->task?->title ?? 'Задача' }}</span>
                                <small>{{ $attempt->status->value }} · {{ $attempt->created_at->format('H:i:s') }}</small>
                            </div>
                        @empty
                            <p class="contest-muted">Попыток пока нет.</p>
                        @endforelse
                    @endif
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('css')
    @vite(['resources/css/contest/contest.css'])
@endpush

@push('js')
    @if(!$isStarted && $startsAtIso)
        <script>
            (() => {
                const panel = document.querySelector('[data-contest-countdown]');
                if (!panel) {
                    return;
                }

                const startAt = new Date(panel.dataset.startAt).getTime();
                const days = panel.querySelector('[data-countdown-days]');
                const hours = panel.querySelector('[data-countdown-hours]');
                const minutes = panel.querySelector('[data-countdown-minutes]');
                const seconds = panel.querySelector('[data-countdown-seconds]');
                let reloading = false;
                const pad = (value) => String(value).padStart(2, '0');

                const tick = () => {
                    const diff = Math.max(0, startAt - Date.now());

                    days.textContent = pad(Math.floor(diff / 86400000));
                    hours.textContent = pad(Math.floor(diff / 3600000) % 24);
                    minutes.textContent = pad(Math.floor(diff / 60000) % 60);
                    seconds.textContent = pad(Math.floor(diff / 1000) % 60);

                    if (diff <= 0 && !reloading) {
                        reloading = true;
                        setTimeout(() => window.location.reload(), 600);
                    }
                };

                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @elseif($isRunning)
        <script>
            setTimeout(() => window.location.reload(), 15000);
        </script>
    @endif
@endpush
