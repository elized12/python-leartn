@extends('layout.main')

@section('content')
    <div class="contest-page">
        <section class="contest-hero">
            <div>
                <h1>Контесты</h1>
                <p>Соревнования с таймером, задачами, таблицей лидеров и живой лентой попыток.</p>
            </div>
            <a href="{{ route('tasks.show') }}" class="contest-secondary-link">Тренироваться</a>
        </section>

        @if($finishedContests->isNotEmpty())
            <section class="contest-panel contest-results-panel">
                <div class="contest-panel-header">
                    <div>
                        <span class="contest-section-kicker">Архив</span>
                        <h2>Завершённые контесты</h2>
                    </div>
                    <span>{{ $finishedContests->count() }}</span>
                </div>

                <div class="contest-results-grid">
                    @foreach($finishedContests as $contest)
                        <a href="{{ route('contests.show', $contest) }}" class="contest-result-card">
                            <div>
                                <span class="contest-status">{{ $contest->statusLabel() }}</span>
                                <h3>{{ $contest->title }}</h3>
                                <p>{{ $contest->ends_at?->format('d.m.Y H:i') ?? 'Финиш не задан' }}</p>
                            </div>
                            <div class="contest-result-winner">
                                <span>Победитель</span>
                                <strong>{{ $contest->winner?->name ?? '—' }}</strong>
                                <small>{{ $contest->winner ? $contest->winner->solved . ' решено' : 'пока нет Accepted' }}</small>
                            </div>
                            <div class="contest-card-meta">
                                <span><i class="fas fa-list-check"></i> {{ $contest->tasks_count }} задач</span>
                                <span><i class="fas fa-users"></i> {{ $contest->participants_count }} участников</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="contest-section-title">
            <div>
                <span class="contest-section-kicker">Расписание</span>
                <h2>Актуальные контесты</h2>
            </div>
        </div>

        <div class="contest-list">
            @forelse($contests as $contest)
                <a href="{{ route('contests.show', $contest) }}" class="contest-card">
                    <div class="contest-card-main">
                        <span class="contest-status">{{ $contest->statusLabel() }}</span>
                        <h2>{{ $contest->title }}</h2>
                        <p>{{ $contest->description ?: 'Без описания' }}</p>
                    </div>
                    <div class="contest-card-meta">
                        <span><i class="fas fa-clock"></i> {{ $contest->starts_at?->format('d.m.Y H:i') ?? 'Старт не задан' }}</span>
                        <span><i class="fas fa-list-check"></i> {{ $contest->tasks_count }} задач</span>
                        <span><i class="fas fa-users"></i> {{ $contest->participants_count }} участников</span>
                    </div>
                </a>
            @empty
                <div class="contest-empty">Активных контестов пока нет.</div>
            @endforelse
        </div>

        {{ $contests->links() }}
    </div>
@endsection

@push('css')
    @vite(['resources/css/contest/contest.css'])
@endpush
