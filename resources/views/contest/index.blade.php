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
