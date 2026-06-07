@extends('layout.main')

@push('css')
    @vite(['resources/css/user/profile.css'])
@endpush

@php
    $formatMemoryMb = fn($value) => $value === null ? '—' : number_format((float) $value, 1, '.', '');
@endphp

@section('content')
    <div class="profile-page">
        <section class="profile-hero">
            <div class="profile-identity">
                <div class="profile-avatar" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <span class="profile-kicker">Профиль ученика</span>
                    <h1>{{ $user->name }}</h1>
                    <p>Здесь видно прогресс по задачам и курсам, к которым подключился пользователь.</p>
                </div>

                @if(auth()->check() && auth()->id() === $user->id)
                    <form method="POST" action="{{ route('logout') }}" class="profile-logout-form">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Выйти</button>
                    </form>
                @endif
            </div>

            <div class="profile-stats">
                <a class="profile-stat rating-stat" href="{{ route('rating.index') }}">
                    <span>Место в рейтинге</span>
                    <strong>{{ $userRating ? '#' . $userRating->rank : '—' }}</strong>
                    <small>{{ $userRating ? $userRating->score . ' очков' : 'нет Accepted' }}</small>
                </a>
                <div class="profile-stat">
                    <span>Задач решено</span>
                    <strong>{{ $tasksCompletedCount }}</strong>
                </div>
                <div class="profile-stat">
                    <span>Попыток</span>
                    <strong>{{ $attemptsCount }}</strong>
                </div>
                <div class="profile-stat">
                    <span>Курсов начато</span>
                    <strong>{{ $joinedCourses->count() }}</strong>
                </div>
                <div class="profile-stat">
                    <span>Курсов пройдено</span>
                    <strong>{{ $completedCourses->count() }}</strong>
                </div>
            </div>
        </section>

        <section class="profile-panel rating-panel">
            <div class="section-heading">
                <div>
                    <span>Рейтинг</span>
                    <h2>Позиция ученика</h2>
                </div>
                <a href="{{ route('rating.index') }}">Вся таблица</a>
            </div>

            @if($userRating)
                <div class="rating-summary-grid">
                    <div class="rating-main-score">
                        <span>#{{ $userRating->rank }}</span>
                        <strong>{{ $userRating->score }}</strong>
                        <small>очков</small>
                    </div>
                    <div class="rating-detail-list">
                        <div>
                            <span>Решено задач</span>
                            <strong>{{ $userRating->solved_tasks }}</strong>
                        </div>
                        <div>
                            <span>С первой попытки</span>
                            <strong>{{ $userRating->first_try_solutions }}</strong>
                        </div>
                        <div>
                            <span>Среднее попыток</span>
                            <strong>{{ $userRating->average_attempts }}</strong>
                        </div>
                    </div>
                    <div class="rating-difficulty-line">
                        <span class="easy">Начинающие: {{ $userRating->easy_solved }}</span>
                        <span class="medium">Средние: {{ $userRating->medium_solved }}</span>
                        <span class="hard">Сложные: {{ $userRating->hard_solved }}</span>
                    </div>
                </div>
            @else
                <div class="profile-empty compact">
                    <strong>Пользователь еще не в рейтинге</strong>
                    <p>Рейтинг появится после первой решенной задачи.</p>
                    <a href="{{ route('tasks.show') }}">Перейти к задачам</a>
                </div>
            @endif
        </section>

        <section class="profile-panel knowledge-panel">
            <div class="section-heading">
                <div>
                    <span>Knowledge Tracing</span>
                    <h2>Профиль знаний</h2>
                </div>
                <a href="{{ route('tasks.show') }}">Практиковаться</a>
            </div>

            @if($knowledgeProfile->isNotEmpty())
                <div class="knowledge-grid">
                    @foreach($knowledgeProfile as $item)
                        <article class="knowledge-item {{ $item->level }}">
                            <div class="knowledge-item-head">
                                <strong>{{ $item->category_name }}</strong>
                                <span>{{ $item->percentage }}%</span>
                            </div>
                            <div class="knowledge-progress">
                                <span style="width: {{ $item->percentage }}%"></span>
                            </div>
                            <small>{{ $item->level_label }}</small>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="profile-empty compact">
                    <strong>Профиль знаний еще пуст</strong>
                    <p>Он появится после первых отправок решений по задачам с категориями.</p>
                </div>
            @endif
        </section>

        <section class="profile-panel recommended-panel">
            <div class="section-heading">
                <div>
                    <span>Рекомендации</span>
                    <h2>Что решить дальше</h2>
                </div>
                <a href="{{ route('tasks.show') }}">Все задачи</a>
            </div>

            @if($recommendedTasks->isNotEmpty())
                <div class="recommended-task-list">
                    @foreach($recommendedTasks as $task)
                        <a class="recommended-task-card" href="{{ route('task.solution', ['taskId' => $task->id]) }}">
                            <div>
                                <strong>{{ $task->title }}</strong>
                                <p>{{ $task->recommendation_reason }}</p>
                            </div>
                            <span>{{ $task->rating }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="profile-empty compact">
                    <strong>Рекомендаций пока нет</strong>
                    <p>Добавьте задачи с категориями или решите несколько стартовых задач.</p>
                </div>
            @endif
        </section>

        <section class="profile-panel profile-contests-panel">
            <div class="section-heading">
                <div>
                    <span>Контесты</span>
                    <h2>Участие и места</h2>
                </div>
                <a href="{{ route('contests.index') }}">Все контесты</a>
            </div>

            @if($contestResults->isNotEmpty())
                <div class="profile-contest-list">
                    @foreach($contestResults as $contest)
                        @php
                            $result = $contest->user_contest_result;
                        @endphp
                        <a class="profile-contest-card" href="{{ route('contests.show', $contest) }}">
                            <div class="profile-contest-rank">
                                <span>{{ $contest->statusLabel() }}</span>
                                <strong>{{ $result ? '#' . $result->rank : '—' }}</strong>
                            </div>
                            <div class="profile-contest-main">
                                <h3>{{ $contest->title }}</h3>
                                <div class="profile-contest-meta">
                                    <span>{{ $contest->starts_at?->format('d.m.Y H:i') ?? 'Старт не задан' }}</span>
                                    <span>{{ $contest->tasks_count }} задач</span>
                                    <span>{{ $contest->participants_count }} участников</span>
                                </div>
                            </div>
                            <div class="profile-contest-score">
                                <strong>{{ $result?->solved ?? 0 }}</strong>
                                <span>решено</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="profile-empty compact">
                    <strong>Контестов пока нет</strong>
                    <p>Когда пользователь присоединится к контесту, его место появится здесь.</p>
                    <a href="{{ route('contests.index') }}">Перейти к контестам</a>
                </div>
            @endif
        </section>

        <div class="profile-grid">
            <section class="profile-panel learning-panel">
                <div class="section-heading">
                    <div>
                        <span>Обучение</span>
                        <h2>Курсы в процессе</h2>
                    </div>
                    <a href="{{ route('courses.show', ['filter' => 'my']) }}">Все мои курсы</a>
                </div>

                <div class="course-progress-list">
                    @forelse($activeCourses as $course)
                        @include('user.partials.profile-course-card', ['course' => $course])
                    @empty
                        <div class="profile-empty compact">
                            <strong>Активных курсов нет</strong>
                            <p>Когда пользователь войдет в курс, прогресс появится здесь.</p>
                            <a href="{{ route('courses.show') }}">Выбрать курс</a>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="profile-panel completed-courses-panel">
                <div class="section-heading">
                    <div>
                        <span>Финиш</span>
                        <h2>Пройденные курсы</h2>
                    </div>
                </div>

                <div class="completed-course-list">
                    @forelse($completedCourses as $course)
                        <a class="completed-course-item" href="{{ route('course.show', ['courseName' => $course->url]) }}">
                            <span>{{ $course->title }}</span>
                            <strong>{{ $course->lessons_count }} уроков</strong>
                        </a>
                    @empty
                        <div class="profile-empty compact">
                            <strong>Пока нет завершенных курсов</strong>
                            <p>Курс попадет сюда, когда будут отмечены все уроки.</p>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>

        <section class="profile-panel solved-panel">
            <div class="section-heading">
                <div>
                    <span>Практика</span>
                    <h2>Последние решенные задачи</h2>
                </div>
                <a href="{{ route('tasks.show', ['status' => 'solved']) }}">Открыть задачи</a>
            </div>

            @if($tasksCompleted->isNotEmpty())
                <div class="solved-task-list">
                    @foreach($tasksCompleted as $attempt)
                        @php
                            $task = $attempt->task;
                            if (!$task) {
                                continue;
                            }

                            $difficulty = match (true) {
                                $task->rating < 1200 => ['class' => 'easy', 'label' => 'Начинающий'],
                                $task->rating < 1800 => ['class' => 'medium', 'label' => 'Средний'],
                                default => ['class' => 'hard', 'label' => 'Профи'],
                            };
                        @endphp

                        <article class="solved-task-card">
                            <div>
                                <a href="{{ route('task.solution', ['taskId' => $task->id]) }}">{{ $task->title }}</a>
                                <div class="task-meta-line">
                                    <span class="difficulty-pill {{ $difficulty['class'] }}">{{ $difficulty['label'] }}</span>
                                    @foreach($task->categories->take(2) as $category)
                                        <span>{{ $category->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="task-result-meta">
                                <strong>{{ $attempt->created_at?->format('d.m.Y') }}</strong>
                                <span>{{ $attempt->execution_time_s ?? '—' }} сек. · {{ $formatMemoryMb($attempt->peak_memory_usage_mb) }} МБ</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="profile-empty">
                    <strong>Пока нет решенных задач</strong>
                    <p>После первого Accepted здесь появится история решений.</p>
                    <a href="{{ route('tasks.show') }}">Перейти к задачам</a>
                </div>
            @endif
        </section>
    </div>
@endsection
