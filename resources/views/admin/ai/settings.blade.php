@extends('layout.admin')

@section('title', 'Настройки нейросети')

@section('content')
    <div class="admin-page-grid">
        <section class="admin-card">
            <h2>Текущая модель подсказок</h2>

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

            @if($connectionError)
                <div class="alert alert-danger">
                    {{ $connectionError }} Проверьте, что Ollama запущена командой <code>ollama serve</code>.
                </div>
            @endif

            <div class="admin-stats-grid">
                <div class="stat-card">
                    <span>Активная модель</span>
                    <strong>{{ $currentModel }}</strong>
                </div>
                <div class="stat-card">
                    <span>Ollama API</span>
                    <strong>{{ $ollamaBaseUrl }}</strong>
                </div>
                <div class="stat-card">
                    <span>Chat endpoint</span>
                    <strong>{{ $ollamaChatUrl }}</strong>
                </div>
                <div class="stat-card">
                    <span>Удержание в памяти</span>
                    <strong>{{ $keepAlive }}</strong>
                </div>
            </div>

            <form action="{{ route('admin.ai-settings.update') }}" method="POST" class="admin-form">
                @csrf
                @method('PUT')

                <label>
                    Выбрать установленную модель
                    <select name="model" required>
                        @if(count($models) === 0)
                            <option value="{{ $currentModel }}">{{ $currentModel }}</option>
                        @endif
                        @foreach($models as $model)
                            <option value="{{ $model['name'] }}" @selected($model['name'] === $currentModel)>
                                {{ $model['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <span class="muted">
                        После сохранения модель сразу загрузится в Ollama. Значение <code>OLLAMA_KEEP_ALIVE=-1</code>
                        удерживает её в оперативной памяти до перезапуска Ollama.
                    </span>
                </label>

                <button type="submit" class="btn btn-primary">Сохранить модель</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Установить новую модель</h2>

            <form action="{{ route('admin.ai-settings.install') }}" method="POST" class="admin-form">
                @csrf

                <label>
                    Название модели Ollama
                    <input type="text" name="model" value="{{ old('model', 'qwen2.5-coder:3b') }}"
                        placeholder="qwen2.5-coder:3b" required>
                    <span class="muted">
                        Используется команда Ollama pull через локальный API. Например: qwen2.5-coder:3b, llama3.1:8b.
                    </span>
                </label>

                <label class="checkbox-row">
                    <input type="checkbox" name="make_active" value="1" checked>
                    Сразу выбрать модель для подсказок
                </label>

                <button type="submit" class="btn btn-success"
                    onclick="return confirm('Установка модели может занять несколько минут. Запустить установку?')">
                    Установить модель
                </button>
            </form>

            <div class="admin-hint">
                Если Laravel запущен не в Docker, для локальной Ollama подходит:
                <code>OLLAMA_URL=http://127.0.0.1:11434/api/chat</code>
                После изменения .env может понадобиться:
                <code>php artisan config:clear</code>
            </div>
        </section>
    </div>

    <section class="admin-card ai-prompt-card" style="margin-top: 18px;">
        <div class="card-heading">
            <div>
                <span class="eyebrow">Prompt</span>
                <h2>Редактор промпта подсказок</h2>
            </div>
        </div>

        <div class="ai-prompt-layout">
            <form action="{{ route('admin.ai-settings.prompt.update') }}" method="POST" class="admin-form ai-prompt-form">
                @csrf
                @method('PUT')

                <label>
                    Название версии промпта
                    <input type="text" name="name" value="{{ old('name', 'Prompt ' . now()->format('d.m.Y H:i')) }}" maxlength="120" placeholder="Например: v2.1 — объяснение ошибок">
                    <span class="muted">
                        Каждая сохранённая версия записывается в БД и не удаляется, чтобы можно было анализировать, какой промпт использовался для конкретной попытки.
                    </span>
                </label>

                <div class="admin-stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 12px;">
                    <label>
                        Temperature
                        <input type="text" inputmode="decimal" name="temperature"
                            value="{{ old('temperature', $aiOptions['temperature'] ?? config('ollama.temperature', 0.2)) }}"
                            placeholder="0.2 или 0,2">
                        <span class="muted">Можно вводить с точкой или запятой. В Ollama уйдёт float.</span>
                    </label>
                    <label>
                        num_ctx
                        <input type="number" min="1" max="32768" name="num_ctx" value="{{ old('num_ctx', $aiOptions['num_ctx'] ?? config('ollama.num_ctx', 8192)) }}">
                    </label>
                    <label>
                        num_predict
                        <input type="number" min="1" max="8192" name="num_predict" value="{{ old('num_predict', $aiOptions['num_predict'] ?? config('ollama.num_predict', 550)) }}">
                    </label>
                </div>

                <label>
                    System prompt
                    <textarea name="system_prompt" rows="14" required>{{ old('system_prompt', $systemPrompt) }}</textarea>
                    <span class="muted">
                        Здесь задается роль нейросети, правила поведения и формат ответа.
                    </span>
                </label>

                <label>
                    User prompt
                    <textarea name="user_prompt" rows="18" required>{{ old('user_prompt', $userPrompt) }}</textarea>
                    <span class="muted">
                        Здесь описывается конкретная попытка ученика. Вставляйте переменные в формате <code>@{{variable}}</code>.
                    </span>
                </label>

                <button type="submit" class="btn btn-primary">Сохранить промпт как новую версию</button>
            </form>

            <aside class="ai-prompt-help">
                <h3>Доступные переменные</h3>
                <p class="muted">Их можно вставлять в User prompt. Перед отправкой в Ollama они заменяются реальными данными последней попытки.</p>

                <div class="ai-variable-list">
                    @foreach($promptVariables as $variable => $description)
                        @php($placeholder = '{{' . $variable . '}}')
                        <button type="button" class="ai-variable-copy" data-variable="{{ $placeholder }}">
                            <code>{{ $placeholder }}</code>
                            <span>{{ $description }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="admin-hint">
                    Минимально полезный шаблон должен содержать:
                    <code>@{{task_description}}</code>
                    <code>@{{student_code}}</code>
                    <code>@{{check_status}}</code>
                    <code>@{{checker_message}}</code>
                    Для понимания формата ввода полезно добавлять:
                    <code>@{{tests}}</code>
                </div>
            </aside>
        </div>
    </section>

    <section class="admin-card" style="margin-top: 18px;">
        <h2>Установленные модели</h2>

        <div class="admin-stack">
            @forelse($models as $model)
                <article class="admin-list-item">
                    <div class="admin-list-header">
                        <strong>{{ $model['name'] }}</strong>
                        @if($model['name'] === $currentModel)
                            <span>используется сейчас</span>
                        @else
                            <span>{{ round($model['size'] / 1024 / 1024 / 1024, 2) }} GB</span>
                        @endif
                    </div>

                    <p class="muted">
                        @if($model['modified_at'])
                            Обновлена: {{ \Carbon\Carbon::parse($model['modified_at'])->format('d.m.Y H:i') }}
                        @else
                            Дата обновления неизвестна
                        @endif
                    </p>

                    @if($model['name'] !== $currentModel)
                        <form action="{{ route('admin.ai-settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="model" value="{{ $model['name'] }}">
                            <button type="submit" class="btn btn-primary">Выбрать</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="empty-state">
                    Список моделей недоступен. Запустите Ollama или установите первую модель.
                </p>
            @endforelse
        </div>
    </section>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userPrompt = document.querySelector('textarea[name="user_prompt"]');

            document.querySelectorAll('.ai-variable-copy').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!userPrompt) return;

                    const value = button.dataset.variable || '';
                    const start = userPrompt.selectionStart ?? userPrompt.value.length;
                    const end = userPrompt.selectionEnd ?? userPrompt.value.length;

                    userPrompt.value = `${userPrompt.value.slice(0, start)}${value}${userPrompt.value.slice(end)}`;
                    userPrompt.focus();
                    userPrompt.selectionStart = start + value.length;
                    userPrompt.selectionEnd = start + value.length;
                });
            });
        });
    </script>
@endpush
