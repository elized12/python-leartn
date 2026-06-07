@extends('layout.admin')

@section('title', 'Контесты')

@section('content')
    @php
        $contestCollection = $contests->getCollection();
        $runningCount = $contestCollection->filter(fn($contest) => $contest->isRunning())->count();
        $plannedCount = $contestCollection->filter(fn($contest) => $contest->is_active && !$contest->isStarted())->count();
        $participantsCount = $contestCollection->sum('participants_count');
        $statusClass = fn($contest) => match ($contest->statusLabel()) {
            'Идёт' => 'running',
            'Скоро' => 'planned',
            'Завершён' => 'finished',
            default => 'hidden',
        };
    @endphp

    <div class="contest-admin-page">
        <section class="contest-admin-hero">
            <div>
                <span class="contest-admin-eyebrow">Соревнования</span>
                <h1>Контесты</h1>
                <p>Создавайте соревнования, выбирайте задачи, управляйте стартом и следите за участниками.</p>
            </div>
            <button class="btn btn-primary" type="button" onclick="document.getElementById('create-form').scrollIntoView({behavior:'smooth'});">
                Создать контест
            </button>
        </section>

        <div class="contest-admin-stats">
            <div class="contest-stat-card">
                <span>Всего</span>
                <strong>{{ $contests->total() }}</strong>
            </div>
            <div class="contest-stat-card">
                <span>Идут сейчас</span>
                <strong>{{ $runningCount }}</strong>
            </div>
            <div class="contest-stat-card">
                <span>Запланированы</span>
                <strong>{{ $plannedCount }}</strong>
            </div>
            <div class="contest-stat-card">
                <span>Участники</span>
                <strong>{{ $participantsCount }}</strong>
            </div>
        </div>

        @if(session('success'))
            <div class="contest-alert success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="contest-alert error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="contest-editor-card" id="create-form">
            <div class="contest-section-heading">
                <div>
                    <span>Новый контест</span>
                    <h2>Параметры соревнования</h2>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.contests.store') }}" class="contest-form">
                @csrf
                @include('admin.contests.partials.form', ['contest' => null, 'tasks' => $tasks])
                <div class="contest-form-actions">
                    <button class="btn btn-primary" type="submit">Сохранить контест</button>
                </div>
            </form>
        </section>

        <section class="contest-section-heading">
            <div>
                <span>Список</span>
                <h2>Созданные контесты</h2>
            </div>
        </section>

        <div class="contest-admin-list">
            @forelse($contests as $contest)
                <article class="contest-admin-card">
                    <div class="contest-card-top">
                        <div class="contest-title-group">
                            <span class="contest-status-pill status-{{ $statusClass($contest) }}">{{ $contest->statusLabel() }}</span>
                            <h3>{{ $contest->title }}</h3>
                            <p>{{ $contest->description ?: 'Описание пока не заполнено.' }}</p>
                        </div>
                        <div class="contest-card-actions">
                            <form method="POST" action="{{ route('admin.contests.start-now', $contest) }}">
                                @csrf
                                <button class="btn btn-success" type="submit">Стартовать</button>
                            </form>
                            <form method="POST" action="{{ route('admin.contests.destroy', $contest) }}"
                                onsubmit="return confirm('Удалить контест? Это действие нельзя отменить.');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Удалить</button>
                            </form>
                        </div>
                    </div>

                    <div class="contest-meta-grid">
                        <div>
                            <span>Старт</span>
                            <strong>{{ $contest->starts_at?->format('d.m.Y H:i') ?? 'Не задан' }}</strong>
                        </div>
                        <div>
                            <span>Финиш</span>
                            <strong>{{ $contest->ends_at?->format('d.m.Y H:i') ?? 'Не задан' }}</strong>
                        </div>
                        <div>
                            <span>Длительность</span>
                            <strong>{{ $contest->duration_minutes }} мин.</strong>
                        </div>
                        <div>
                            <span>Задачи</span>
                            <strong>{{ $contest->tasks_count }}</strong>
                        </div>
                        <div>
                            <span>Участники</span>
                            <strong>{{ $contest->participants_count }}</strong>
                        </div>
                    </div>

                    <details class="contest-edit-details">
                        <summary>Редактировать контест</summary>
                        <form method="POST" action="{{ route('admin.contests.update', $contest) }}" class="contest-form">
                            @csrf
                            @method('PUT')
                            @include('admin.contests.partials.form', ['contest' => $contest, 'tasks' => $tasks])
                            <div class="contest-form-actions">
                                <button class="btn btn-primary" type="submit">Обновить контест</button>
                            </div>
                        </form>
                    </details>
                </article>
            @empty
                <div class="contest-empty-state">
                    <strong>Контестов пока нет</strong>
                    <span>Создайте первый контест и добавьте в него задачи.</span>
                </div>
            @endforelse
        </div>

        <div class="contest-pagination">
            {{ $contests->links() }}
        </div>
    </div>
@endsection

@push('css')
    @vite(['resources/css/admin/contests.css'])
@endpush

@push('js')
    @vite(['resources/js/admin/contests.js'])
@endpush
