@extends('layout.main')

@section('content')
    <div class="contest-page">
        @if(session('success'))
            <div class="contest-alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="contest-alert error">{{ session('error') }}</div>
        @endif

        <section class="contest-hero">
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

        <div class="contest-layout">
            <section class="contest-panel">
                <div class="contest-panel-header">
                    <h2>Задачи</h2>
                    <span>{{ $tasks->count() }}</span>
                </div>
                <div class="contest-task-list">
                    @forelse($tasks as $task)
                        <div class="contest-task-row">
                            <div>
                                <h3>{{ $task->title }}</h3>
                                <span>Рейтинг {{ $task->rating }}</span>
                            </div>
                            @if($contest->isRunning() && $isParticipant)
                                <a href="{{ route('contests.task', ['contest' => $contest, 'task' => $task]) }}">Решать</a>
                            @else
                                <span class="contest-task-locked">{{ $contest->isRunning() ? 'Нужно присоединиться' : 'Недоступно' }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="contest-muted">Задачи пока не выбраны.</p>
                    @endforelse
                </div>
            </section>

            <aside class="contest-side">
                <section class="contest-panel">
                    <div class="contest-panel-header">
                        <h2>Лидеры</h2>
                    </div>
                    @forelse($leaderboard as $row)
                        <div class="contest-leader-row">
                            <strong>{{ $loop->iteration }}. {{ $row->name }}</strong>
                            <span>{{ $row->solved }} решено</span>
                        </div>
                    @empty
                        <p class="contest-muted">Пока нет принятых решений.</p>
                    @endforelse
                </section>

                <section class="contest-panel">
                    <div class="contest-panel-header">
                        <h2>Последние попытки</h2>
                    </div>
                    @forelse($recentAttempts as $attempt)
                        <div class="contest-attempt-row {{ $attempt->status === \App\Service\Task\TaskStatus::COMPLETED ? 'accepted' : '' }}">
                            <strong>{{ $attempt->user?->name ?? 'Пользователь' }}</strong>
                            <span>{{ $attempt->task?->title ?? 'Задача' }}</span>
                            <small>{{ $attempt->status->value }} · {{ $attempt->created_at->format('H:i:s') }}</small>
                        </div>
                    @empty
                        <p class="contest-muted">Попыток пока нет.</p>
                    @endforelse
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('css')
    @vite(['resources/css/contest/contest.css'])
@endpush

@push('js')
    @if($contest->isRunning())
        <script>
            setTimeout(() => window.location.reload(), 15000);
        </script>
    @endif
@endpush
