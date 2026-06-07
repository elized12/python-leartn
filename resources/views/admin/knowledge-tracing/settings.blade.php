@extends('layout.admin')

@section('title', 'Настройки BKT')

@section('content')
    <div class="admin-page-grid">
        <section class="admin-card">
            <h2>Параметры Bayesian Knowledge Tracing</h2>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.knowledge-tracing.update') }}" method="POST" class="admin-form">
                @csrf
                @method('PUT')

                <div class="admin-stats-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <label>
                        Prior
                        <input type="number" name="prior" min="0" max="1" step="0.01"
                            value="{{ old('prior', $settings['prior']) }}" required>
                        <span class="muted">Начальная вероятность знания новой категории.</span>
                    </label>

                    <label>
                        Learn
                        <input type="number" name="learn" min="0" max="1" step="0.01"
                            value="{{ old('learn', $settings['learn']) }}" required>
                        <span class="muted">Вероятность, что навык усилится после попытки.</span>
                    </label>

                    <label>
                        Guess
                        <input type="number" name="guess" min="0" max="1" step="0.01"
                            value="{{ old('guess', $settings['guess']) }}" required>
                        <span class="muted">Вероятность угадать правильный ответ без знания.</span>
                    </label>

                    <label>
                        Slip
                        <input type="number" name="slip" min="0" max="1" step="0.01"
                            value="{{ old('slip', $settings['slip']) }}" required>
                        <span class="muted">Вероятность ошибиться, даже если навык освоен.</span>
                    </label>
                </div>

                <div class="bkt-settings-divider">
                    <h3>Ограничение по сложности задач</h3>
                    <p class="muted">Если ученик решает только лёгкие задачи, тема не поднимется выше указанного потолка.</p>
                </div>

                <div class="admin-stats-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <label>
                        Максимальный рейтинг лёгкой задачи
                        <input type="number" name="easy_rating_max" min="0" max="5000" step="1"
                            value="{{ old('easy_rating_max', $settings['easy_rating_max']) }}" required>
                        <span class="muted">До этого рейтинга задача считается лёгкой.</span>
                    </label>

                    <label>
                        Максимальный рейтинг средней задачи
                        <input type="number" name="medium_rating_max" min="0" max="5000" step="1"
                            value="{{ old('medium_rating_max', $settings['medium_rating_max']) }}" required>
                        <span class="muted">Выше этого рейтинга задача считается сложной.</span>
                    </label>

                    <label>
                        Потолок навыка после лёгких задач
                        <input type="number" name="easy_mastery_cap" min="0" max="1" step="0.01"
                            value="{{ old('easy_mastery_cap', $settings['easy_mastery_cap']) }}" required>
                        <span class="muted">Например, 0.70 означает максимум 70%.</span>
                    </label>

                    <label>
                        Потолок навыка после средних задач
                        <input type="number" name="medium_mastery_cap" min="0" max="1" step="0.01"
                            value="{{ old('medium_mastery_cap', $settings['medium_mastery_cap']) }}" required>
                        <span class="muted">Средние задачи могут поднять навык выше лёгких.</span>
                    </label>

                    <label>
                        Потолок навыка после сложных задач
                        <input type="number" name="hard_mastery_cap" min="0" max="1" step="0.01"
                            value="{{ old('hard_mastery_cap', $settings['hard_mastery_cap']) }}" required>
                        <span class="muted">Обычно 1.00, чтобы сложные задачи могли подтверждать полное освоение.</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить параметры</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Как это работает</h2>

            <div class="admin-stack">
                <article class="admin-list-item compact">
                    <strong>Категория = навык</strong>
                    <p class="muted">Если задача связана с категориями «циклы» и «списки», BKT обновляет обе темы.</p>
                </article>

                <article class="admin-list-item compact">
                    <strong>Повторные отправки не накручивают прогресс</strong>
                    <p class="muted">Одна задача влияет на профиль знаний пользователя только один раз.</p>
                </article>

                <article class="admin-list-item compact">
                    <strong>Значения по умолчанию</strong>
                    <p class="muted">
                        Prior {{ $defaults['prior'] }},
                        Learn {{ $defaults['learn'] }},
                        Guess {{ $defaults['guess'] }},
                        Slip {{ $defaults['slip'] }}.
                        Потолки сложности: лёгкие {{ $defaults['easy_mastery_cap'] * 100 }}%,
                        средние {{ $defaults['medium_mastery_cap'] * 100 }}%,
                        сложные {{ $defaults['hard_mastery_cap'] * 100 }}%.
                    </p>
                </article>
            </div>
        </section>
    </div>

    <section class="admin-card bkt-user-card">
        <div class="admin-list-header">
            <div>
                <span class="muted">Диагностика ученика</span>
                <h2>Профиль знаний пользователя</h2>
            </div>
            @if($selectedUser)
                <a class="btn btn-light" href="{{ route('user.profile', ['userId' => $selectedUser->id]) }}">Открыть профиль</a>
            @endif
        </div>

        <form action="{{ route('admin.knowledge-tracing.index') }}" method="GET" class="admin-form bkt-user-form">
            <label>
                Ученик
                <select name="user_id" required>
                    <option value="">Выберите пользователя</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) $selectedUserId === (int) $user->id)>
                            {{ $user->name }} — {{ $user->email }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn btn-primary">Показать навыки</button>
        </form>

        @if($selectedUserId && !$selectedUser)
            <div class="alert alert-danger bkt-user-alert">Пользователь не найден.</div>
        @elseif($selectedUser)
            <div class="bkt-user-summary">
                <div class="stat-card">
                    <span class="stat-label">Ученик</span>
                    <strong>{{ $selectedUser->name }}</strong>
                    <small>{{ $selectedUser->email }}</small>
                </div>
                <div class="stat-card accent-green">
                    <span class="stat-label">Навыков отслеживается</span>
                    <strong>{{ $knowledgeProfile->count() }}</strong>
                    <small>по категориям задач</small>
                </div>
                <div class="stat-card accent-amber">
                    <span class="stat-label">Слабых тем</span>
                    <strong>{{ $knowledgeProfile->where('level', 'weak')->count() }}</strong>
                    <small>ниже 40%</small>
                </div>
            </div>

            <div class="bkt-admin-grid">
                <section>
                    <h3>Навыки по категориям</h3>
                    @if($knowledgeProfile->isNotEmpty())
                        <div class="bkt-skill-list">
                            @foreach($knowledgeProfile as $item)
                                <article class="bkt-skill-item {{ $item->level }}">
                                    <div class="bkt-skill-head">
                                        <strong>{{ $item->category_name }}</strong>
                                        <span>{{ $item->percentage }}%</span>
                                    </div>
                                    <div class="bkt-progress">
                                        <span style="width: {{ $item->percentage }}%"></span>
                                    </div>
                                    <small>{{ $item->level_label }}</small>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="empty-state">У пользователя пока нет обработанных категорий. Профиль появится после отправок решений.</p>
                    @endif
                </section>

                <section>
                    <h3>Рекомендуемые задачи</h3>
                    @if($recommendedTasks->isNotEmpty())
                        <div class="admin-stack">
                            @foreach($recommendedTasks as $task)
                                <a class="bkt-recommendation-item" href="{{ route('task.solution', ['taskId' => $task->id]) }}">
                                    <div>
                                        <strong>{{ $task->title }}</strong>
                                        <p>{{ $task->recommendation_reason }}</p>
                                    </div>
                                    <span>{{ $task->rating }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="empty-state">Нет подходящих рекомендаций: возможно, все доступные задачи уже решены.</p>
                    @endif
                </section>
            </div>
        @else
            <p class="empty-state bkt-empty-state">Выберите ученика, чтобы посмотреть его освоение тем и рекомендации.</p>
        @endif
    </section>
@endsection
