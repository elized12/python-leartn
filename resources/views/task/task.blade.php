<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Python Code Challenge</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
</head>

<body class="{{ $hasSolvedTask ? 'task-solved' : '' }}">
    @php
        if ($task->rating < 1200) {
            $levelTask = 'Начинающий';
            $classTask = 'easy';
        } elseif (1200 <= $task->rating && $task->rating < 1800) {
            $levelTask = 'Средний';
            $classTask = 'medium';
        } else {
            $levelTask = 'Профессионал';
            $classTask = 'hard';
        }

        $formatMemoryMb = fn($value) => $value === null ? '—' : number_format((float) $value, 1, '.', '');
    @endphp

    <div class="solve-celebration" aria-hidden="true">
        <div class="solve-celebration-card">
            <span class="solve-check">✓</span>
            <strong>Задача решена</strong>
            <span>Авторское решение открыто</span>
        </div>
    </div>

    <div class="leetcode-shell">
        <main class="problem-panel">
            <div class="problem-topbar">
                <a class="back-link" href="{{ $contest ? route('contests.show', $contest) : route('home') }}">Назад</a>
                @if($contest)
                    <span class="locked-badge">Контест: {{ $contest->title }}</span>
                @endif
                @if($hasSolvedTask)
                    <span class="solved-badge">Решено</span>
                @else
                    <span class="locked-badge">В процессе</span>
                @endif
            </div>

            <div class="problem-scroll">
                <div class="task-summary {{ $hasSolvedTask ? 'is-solved' : '' }}">
                    <div class="task-tags">
                        @forelse($task->categories as $category)
                            <a class="task-tag" href="{{ route('tasks.show', ['category' => $category->slug]) }}">
                                {{ $category->name }}
                            </a>
                        @empty
                            <span class="task-category">Без категории</span>
                        @endforelse
                    </div>

                    <h1 class="task-title">{{ $task->title }}</h1>

                    <div class="summary-row">
                        <span class="task-difficulty {{ $classTask }}">{{ $levelTask }}</span>
                    </div>

                    <div class="task-stat-grid" aria-label="Лимиты задачи">
                        <div>
                            <span>Время</span>
                            <strong>{{ $task->time_limit_s ?? 15 }} сек.</strong>
                        </div>
                        <div>
                            <span>Память</span>
                            <strong>{{ $task->memory_limit_mb ?? 128 }} МБ</strong>
                        </div>
                        <div>
                            <span>Попытки</span>
                            <strong>{{ $attempts->count() }}</strong>
                        </div>
                    </div>
                </div>

                <nav class="task-tabs" aria-label="Разделы задачи">
                    <button type="button" class="task-tab active" data-tab="statement">
                        <span>Описание</span>
                    </button>
                    <button type="button" class="task-tab" data-tab="public-tests">
                        <span>Тесты</span>
                        <small>{{ count($publicTests) }}</small>
                    </button>
                    <button type="button" class="task-tab" data-tab="attempts">
                        <span>Попытки</span>
                        <small>{{ $attempts->count() }}</small>
                    </button>
                    <button type="button" class="task-tab" data-tab="comments">
                        <span>Комментарии</span>
                        <small>{{ $task->comments->count() }}</small>
                    </button>
                    <button type="button" class="task-tab {{ $hasSolvedTask ? '' : 'is-locked' }}" data-tab="author-solution">
                        <span>Решение</span>
                        @unless($hasSolvedTask)
                            <small>lock</small>
                        @endunless
                    </button>
                    <button type="button" class="task-tab {{ $hasSolvedTask ? '' : 'is-locked' }}" data-tab="best-solutions">
                        <span>Лучшие</span>
                        @unless($hasSolvedTask)
                            <small>lock</small>
                        @endunless
                    </button>
                </nav>

            <section class="tab-panel active" data-tab-panel="statement">
                <div class="task-card">
                    <div class="panel-title">
                        <span>Описание</span>
                    </div>

                    <div class="task-description markdown-content">
                        {!! $taskDescriptionHtml !!}
                    </div>

                    @if(trim((string) $task->example) !== '')
                        <div class="task-example">
                            <div class="task-example-title">Примеры</div>
                            <div class="task-example-content markdown-content">
                                {!! $taskExampleHtml !!}
                            </div>
                        </div>
                    @endif

                    @if($publicFiles->isNotEmpty())
                        <div class="public-files-card">
                            <h2>Файлы к задаче</h2>
                            <div class="public-files-list">
                                @foreach($publicFiles as $file)
                                    <a href="{{ route('task.files.download', ['taskId' => $task->id, 'fileId' => $file->id]) }}"
                                        class="public-file-link">
                                        <span>{{ basename($file->file_path) }}</span>
                                        <strong>Скачать</strong>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="public-tests">
                <div class="task-card public-tests-card">
                    <div class="panel-title">
                        <span>Публичные тесты</span>
                    </div>

                    <div class="public-tests-list">
                        @forelse($publicTests as $test)
                            <article class="public-test-item">
                                <div class="public-test-header">
                                    <strong>Тест #{{ $test['number'] ?? $loop->iteration }}</strong>
                                    <span>public</span>
                                </div>
                                <div class="public-test-grid">
                                    <div>
                                        <span>Input</span>
                                        <pre><code>{{ $test['input'] ?? '' }}</code></pre>
                                    </div>
                                    <div>
                                        <span>Expected</span>
                                        <pre><code>{{ $test['expected'] ?? $test['expected_output'] ?? '' }}</code></pre>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="empty-comments">Для этой задачи пока нет публичных тестов.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="comments">
                <div class="task-card comments-card">
                    <div class="panel-title">
                        <span>Комментарии</span>
                    </div>

                    @if(session('success'))
                        <div class="comment-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('task.comments.store', ['taskId' => $task->id]) }}" method="POST" class="comment-form">
                        @csrf
                        <textarea name="content" id="comment-content"
                            placeholder="Напишите комментарий к задаче. Markdown поддерживается..." required>{{ old('content') }}</textarea>
                        @error('content')
                            <div class="comment-error">{{ $message }}</div>
                        @enderror
                        <div class="comment-preview" id="comment-preview" hidden>
                            <div class="comment-preview-title">Превью</div>
                            <div class="markdown-content" id="comment-preview-content"></div>
                        </div>
                        <button type="submit" class="comment-submit">Отправить комментарий</button>
                    </form>

                    <div class="comments-list">
                        @forelse($task->comments as $comment)
                            <article class="comment-item">
                                <div class="comment-meta">
                                    <strong>{{ $comment->user?->name ?? 'Пользователь' }}</strong>
                                    <span>{{ $comment->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <div class="comment-body markdown-content">
                                    {!! $commentHtml[$comment->id] ?? e($comment->content) !!}
                                </div>
                            </article>
                        @empty
                            <p class="empty-comments">Пока нет комментариев. Можно быть первым.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="author-solution">
                <div class="task-card author-solution-card {{ $hasSolvedTask ? 'is-open' : 'is-locked' }}"
                    data-author-solution-url="{{ route('task.author-solution.show', ['taskId' => $task->id]) }}">
                    <div class="panel-title">
                        <span>Авторское решение</span>
                    </div>

                    @if($hasSolvedTask)
                        <div class="solution-code-block code-container">
                            <pre class="author-solution"><code class="hljs language-python">{{ $task->reference_solution }}</code></pre>
                        </div>
                    @else
                        <div class="locked-solution">
                            <div class="lock-icon" aria-hidden="true"></div>
                            <h2>Решение пока закрыто</h2>
                            <p class="locked-solution-message">
                                Сначала успешно отправьте своё решение. После Accepted эта вкладка откроется автоматически.
                            </p>
                        </div>
                        <div class="solution-code-block code-container" style="display:none;">
                            <pre class="author-solution"><code class="hljs language-python"></code></pre>
                        </div>
                    @endif
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="attempts">
                <div class="task-card attempts-card">
                    <div class="panel-title">
                        <span>Мои попытки</span>
                    </div>

                    <div class="attempts-list">
                        @forelse($attempts as $attempt)
                            <article class="attempt-item {{ $attempt->status->value === 'Completed' ? 'attempt-success' : 'attempt-failed' }}">
                                <div>
                                    <strong>{{ $attempt->status->value === 'Completed' ? 'Accepted' : $attempt->status->value }}</strong>
                                    <span>{{ $attempt->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <p>{{ $attempt->description }}</p>
                                <div class="attempt-metrics">
                                    <span>{{ $attempt->execution_time_s ?? '—' }} сек.</span>
                                    <span>{{ $formatMemoryMb($attempt->peak_memory_usage_mb) }} МБ</span>
                                    @if(!in_array($attempt->status->value, ['Completed', 'Pending'], true))
                                        <button type="button" class="ai-hint-button attempt-ai-hint-button" data-ai-attempt-id="{{ $attempt->id }}">
                                            {{ $attempt->ai_hint ? 'Открыть подсказку' : 'Спросить ИИ' }}
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="empty-comments">Вы ещё не отправляли решения по этой задаче.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="best-solutions">
                <div class="task-card best-solutions-card {{ $hasSolvedTask ? 'is-open' : 'is-locked' }}">
                    <div class="panel-title">
                        <span>Лучшие решения</span>
                    </div>

                    @if($hasSolvedTask)
                        <div class="leaderboards-grid">
                            <div class="leaderboard-column">
                                <div class="leaderboard-heading">
                                    <h2>Топ-3 по времени</h2>
                                    <span>быстрее - выше</span>
                                </div>
                                @forelse($bestByTime as $attempt)
                                    <article class="leaderboard-item">
                                        <div class="leaderboard-meta">
                                            <strong><span class="rank-badge">{{ $loop->iteration }}</span>{{ $attempt->user?->name ?? 'Пользователь' }}</strong>
                                            <span>{{ $attempt->execution_time_s }} сек.</span>
                                        </div>
                                        <div class="attempt-metrics">
                                            <span>{{ $formatMemoryMb($attempt->peak_memory_usage_mb) }} МБ</span>
                                            <span>{{ $attempt->created_at->format('d.m.Y H:i') }}</span>
                                        </div>
                                        <details>
                                            <summary>Посмотреть код</summary>
                                            <div class="solution-code-block code-container leaderboard-code-block">
                                                <pre class="leaderboard-code"><code class="hljs language-python">{{ $attempt->code }}</code></pre>
                                            </div>
                                        </details>
                                    </article>
                                @empty
                                    <p class="empty-comments">Пока нет успешных решений с измеренным временем.</p>
                                @endforelse
                            </div>

                            <div class="leaderboard-column">
                                <div class="leaderboard-heading">
                                    <h2>Топ-3 по памяти</h2>
                                    <span>меньше - выше</span>
                                </div>
                                @forelse($bestByMemory as $attempt)
                                    <article class="leaderboard-item">
                                        <div class="leaderboard-meta">
                                            <strong><span class="rank-badge">{{ $loop->iteration }}</span>{{ $attempt->user?->name ?? 'Пользователь' }}</strong>
                                            <span>{{ $formatMemoryMb($attempt->peak_memory_usage_mb) }} МБ</span>
                                        </div>
                                        <div class="attempt-metrics">
                                            <span>{{ $attempt->execution_time_s ?? '—' }} сек.</span>
                                            <span>{{ $attempt->created_at->format('d.m.Y H:i') }}</span>
                                        </div>
                                        <details>
                                            <summary>Посмотреть код</summary>
                                            <div class="solution-code-block code-container leaderboard-code-block">
                                                <pre class="leaderboard-code"><code class="hljs language-python">{{ $attempt->code }}</code></pre>
                                            </div>
                                        </details>
                                    </article>
                                @empty
                                    <p class="empty-comments">Память пока не измерялась для успешных решений.</p>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="locked-solution">
                            <div class="lock-icon" aria-hidden="true"></div>
                            <h2>Лучшие решения откроются после Accepted</h2>
                            <p class="locked-solution-message">
                                Так чужие подходы не подскажут решение раньше времени.
                            </p>
                        </div>
                    @endif
                </div>
            </section>
            </div>
        </main>

        <form class="solution-form editor-panel" action="{{route('task.attempt.send', ['taskId' => $task->id])}}" method="POST">
            @csrf
            <div class="text-editor-block task-card">
                <div class="editor-container">
                    <div class="editor-header">
                        <h3>Редактор кода</h3>
                        <div class="editor-actions">
                            <button type="button" class="ai-hint-button"
                                @if($latestAiHintAttempt)
                                    data-ai-attempt-id="{{ $latestAiHintAttempt->id }}"
                                @else
                                    disabled
                                @endif
                            >
                                Спросить ИИ
                            </button>
                            @if($hasSolvedTask)
                                <span class="mini-solved">решено</span>
                            @endif
                        </div>
                    </div>
                    <div id="text-editor"></div>
                    <div id="spinner"></div>
                    <textarea name="code" id="text-editor-textarea"></textarea>
                    @if($contest)
                        <input type="hidden" name="contest_id" value="{{ $contest->id }}">
                    @endif
                    <button type="submit" class="run-button">Запустить код</button>
                    <div class="output-container">
                        <div class="io-title">Вывод:</div>
                        <div id="output"></div>
                        <div id="spinner-output"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="ai-hint-backdrop" id="aiHintBackdrop" hidden></div>
    <aside class="ai-hint-drawer" id="aiHintDrawer" data-ai-hint-url="{{ route('task.ai-hint', ['taskId' => $task->id]) }}"
        aria-hidden="true" aria-labelledby="aiHintTitle">
        <div class="ai-hint-drawer-header">
            <div>
                <span>Разбор ошибки</span>
                <h2 id="aiHintTitle">ИИ-подсказка</h2>
            </div>
            <button type="button" class="ai-hint-close" id="aiHintClose" aria-label="Закрыть подсказку">×</button>
        </div>
        <div class="ai-hint-content markdown-content" id="aiHintContent">
            <p>После неудачной отправки я помогу найти ошибку, но не буду отдавать готовое решение.</p>
        </div>
    </aside>

    <script>
        window.userId = '{{auth()->user()->id}}';
        window.taskId = {{ $task->id }};
        window.contestId = {{ $contest?->id ?? 'null' }};
        window.taskEditorConfig = @json($editorConfig);
    </script>
    @vite(['resources/js/echo.js', 'resources/js/task/task-editor.js', 'resources/css/task/task.css', 'resources/css/shared/markdown.css'])
</body>

</html>
