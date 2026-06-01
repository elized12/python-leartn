@extends('layout.main')

@section('title', 'Задачи')

@section('content')
    @php
        $difficultyLabels = [
            'easy' => 'Легкие',
            'medium' => 'Средние',
            'hard' => 'Сложные',
        ];
        $statusLabels = [
            'solved' => 'Решенные',
            'attempted' => 'Пробовал',
            'unsolved' => 'Не решенные',
        ];
        $baseFilters = [
            'search' => $search ?: null,
            'difficulty' => $activeDifficulty ?: null,
            'status' => $activeStatus ?: null,
            'category' => $activeCategorySlug ?: null,
        ];
    @endphp

    <div class="tasks-shell">
        <section class="tasks-hero">
            <div>
                <span class="tasks-eyebrow">Practice</span>
                <h1>Выбор задач</h1>
                <p>Фильтруйте по категориям, сложности и прогрессу. Решённые задачи подсвечиваются, чтобы легче видеть маршрут обучения.</p>
            </div>

            <form class="tasks-search-form" action="{{ route('tasks.show') }}" method="GET">
                <input type="hidden" name="category" value="{{ $activeCategorySlug }}">
                <input type="hidden" name="difficulty" value="{{ $activeDifficulty }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                <label class="tasks-search">
                    <i class="fas fa-search"></i>
                    <input name="search" type="text" value="{{ $search }}" placeholder="Поиск по названию или категории">
                </label>
                <button type="submit" class="tasks-search-button">Найти</button>
            </form>
        </section>

        <div class="tasks-layout">
            <aside class="tasks-filter-panel">
                <div class="filter-block">
                    <div class="filter-title">Сложность</div>
                    <a class="{{ $activeDifficulty ? '' : 'active' }}"
                        href="{{ route('tasks.show', array_filter([...$baseFilters, 'difficulty' => null])) }}">
                        Все сложности
                    </a>
                    @foreach($difficultyLabels as $value => $label)
                        <a class="{{ $activeDifficulty === $value ? 'active' : '' }}"
                            href="{{ route('tasks.show', array_filter([...$baseFilters, 'difficulty' => $value])) }}">
                            <span class="difficulty-dot {{ $value }}"></span>{{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="filter-block">
                    <div class="filter-title">Статус</div>
                    <a class="{{ $activeStatus ? '' : 'active' }}"
                        href="{{ route('tasks.show', array_filter([...$baseFilters, 'status' => null])) }}">
                        Все задачи
                    </a>
                    @foreach($statusLabels as $value => $label)
                        <a class="{{ $activeStatus === $value ? 'active' : '' }}"
                            href="{{ route('tasks.show', array_filter([...$baseFilters, 'status' => $value])) }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="filter-block">
                    <div class="filter-title">Категории</div>
                    <a class="{{ $activeCategorySlug ? '' : 'active' }}"
                        href="{{ route('tasks.show', array_filter([...$baseFilters, 'category' => null])) }}">
                        Все категории
                    </a>
                    @foreach($categories as $category)
                        <a class="{{ $activeCategorySlug === $category->slug ? 'active' : '' }}"
                            href="{{ route('tasks.show', array_filter([...$baseFilters, 'category' => $category->slug])) }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </aside>

            <main class="tasks-content">
                <section class="ai-recommendation-card">
                    <div>
                        <span class="tasks-eyebrow">AI recommendation</span>
                        <h2>Задача, которую стоит решить дальше</h2>
                        @if($recommendedTask)
                            <p>Нейросеть может использовать это место для персональной рекомендации. Пока выбран ближайший нерешённый вариант по текущим фильтрам.</p>
                        @else
                            <p>По текущим фильтрам нет нерешённых задач. Можно сбросить фильтры или добавить новые задачи.</p>
                        @endif
                    </div>
                    @if($recommendedTask)
                        <a class="recommended-task" href="{{ route('task.solution', ['taskId' => $recommendedTask->id]) }}">
                            <strong>{{ $recommendedTask->title }}</strong>
                            <span>{{ $recommendedTask->rating }} рейтинга</span>
                        </a>
                    @else
                        <a class="recommended-task muted" href="{{ route('tasks.show') }}">
                            <strong>Сбросить фильтры</strong>
                            <span>посмотреть все задачи</span>
                        </a>
                    @endif
                </section>

                <div class="tasks-toolbar">
                    <div>
                        <strong>{{ $tasks->total() }}</strong>
                        <span>задач найдено</span>
                    </div>
                    @if($search || $activeDifficulty || $activeStatus || $activeCategorySlug)
                        <a href="{{ route('tasks.show') }}">Сбросить фильтры</a>
                    @endif
                </div>

                <div class="tasks-list">
                    <div class="tasks-list-header">
                        <span>Статус</span>
                        <span>Задача</span>
                        <span>Сложность</span>
                    </div>

                    @forelse ($tasks as $task)
                        @php
                            $isSolved = $solvedTaskIds->contains($task->id);
                            $isAttempted = !$isSolved && $attemptedTaskIds->contains($task->id);
                            $difficultyClass = $task->rating < 1200 ? 'easy' : ($task->rating < 1800 ? 'medium' : 'hard');
                            $difficultyText = $task->rating < 1200 ? 'Легкая' : ($task->rating < 1800 ? 'Средняя' : 'Сложная');
                        @endphp
                        <article class="task-list-row {{ $isSolved ? 'is-solved' : ($isAttempted ? 'is-attempted' : '') }}">
                            <div class="task-status-cell">
                                @if($isSolved)
                                    <span class="status-icon solved-icon"><i class="fas fa-check"></i></span>
                                @elseif($isAttempted)
                                    <span class="status-icon attempted-icon"><i class="fas fa-clock"></i></span>
                                @else
                                    <span class="status-icon unsolved-icon"></span>
                                @endif
                            </div>

                            <div class="task-main-cell">
                                <a href="{{ route('task.solution', ['taskId' => $task->id]) }}">{{ $task->title }}</a>
                                <div class="task-list-tags">
                                    @forelse($task->categories as $category)
                                        <a href="{{ route('tasks.show', array_filter([...$baseFilters, 'category' => $category->slug])) }}">
                                            {{ $category->name }}
                                        </a>
                                    @empty
                                        <span>Без категории</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="task-difficulty-cell {{ $difficultyClass }}">
                                <span>{{ $difficultyText }}</span>
                                <strong>{{ $task->rating }}</strong>
                            </div>
                        </article>
                    @empty
                        <div class="tasks-empty-state">
                            <strong>Ничего не найдено</strong>
                            <p>Попробуйте изменить поиск, категорию или сложность.</p>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-container">
                    <div class="pagination-wrapper">
                        {{ $tasks->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
