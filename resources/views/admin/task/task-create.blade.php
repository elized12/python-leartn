<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($task) ? 'Изменение задачи' : 'Создание задачи' }}</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/bash.min.js"></script>

    @vite(['resources/css/admin/task/task-create.css', 'resources/css/shared/markdown.css'])

</head>

<body>
    @php
        $isEdit = isset($task);
        $selectedCategoryIds = collect(old(
            'category_ids',
            $isEdit ? $task->categories->pluck('id')->all() : []
        ))
            ->map(fn($id) => (int) $id)
            ->all();
        $testsJsonValue = old(
            'tests_json_content',
            $isEdit
                ? json_encode($task->tests ?? ['tests' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : '{
  "tests": [
    {
      "number": 1,
      "public": true,
      "input": "2 3",
      "expected": "5"
    },
    {
      "number": 2,
      "public": false,
      "files": [
        {
          "name": "students.csv",
          "target": "students.csv",
          "scope": "solution"
        }
      ],
      "expected": "4.5"
    }
  ]
}'
        );
        $runnerMode = old('runner_mode', $isEdit && $task->runner_file_path ? 'runner' : 'solution');
        $checkerType = old('checker_type', $isEdit && $task->checker_file_path ? 'custom' : 'standard');
    @endphp

    <div class="container">
        <div class="form-section">
            <div class="section-title-content"
                style="display: flex; align-items: start; justify-content:space-between;">
                <a class="add-test-case" style="margin-top:0; text-decoration: none;"
                    href="{{ route('admin.tasks.show') }}">Назад</a>
                <h2 class="section-title">{{ $isEdit ? 'Изменение задачи' : 'Создание новой задачи' }}</h2>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    <button type="button" class="close-alert">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <button type="button" class="close-alert">&times;</button>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ $isEdit ? route('admin.task.update', $task) : route('admin.task-create.create') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label for="task-title">Название задачи</label>
                    <input name="title" type="text" id="task-title" placeholder="Например: Two Sum"
                        value="{{ old('title', $isEdit ? $task->title : '') }}">
                </div>

                <div class="form-group">
                    <label for="task-difficulty">Сложность</label>
                    <input name="rating" type="number" id="task-difficulty" min="0" max="5000" name="rating"
                        placeholder="800" value="{{ old('rating', $isEdit ? $task->rating : 800) }}">
                </div>

                <div class="form-group">
                    <label>Категории</label>
                    <small class="field-hint">
                        Можно выбрать только категории, которые уже созданы в админке.
                    </small>
                    <div id="selected-categories" class="selected-categories">
                        @foreach($categories->whereIn('id', $selectedCategoryIds) as $category)
                            <button type="button" class="selected-category" data-category-id="{{ $category->id }}">
                                {{ $category->name }}
                                <span aria-hidden="true">×</span>
                                <input type="hidden" name="category_ids[]" value="{{ $category->id }}">
                            </button>
                        @endforeach
                    </div>
                    @if($categories->isNotEmpty())
                        <div class="category-suggestions">
                            @foreach($categories as $category)
                                <button type="button" class="category-suggestion"
                                    data-category-id="{{ $category->id }}"
                                    data-category-name="{{ $category->name }}"
                                    @disabled(in_array($category->id, $selectedCategoryIds, true))>
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="field-hint">Сначала создайте категории на странице категорий.</p>
                    @endif
                </div>

                <div class="form-group">
                    <label for="task-description">Описание задачи</label>
                    <textarea name="description" id="task-description"
                        placeholder="Подробное описание задачи в Markdown...">{{ old('description', $isEdit ? $task->description : '') }}</textarea>
                    <small class="field-hint">
                        Поддерживается Markdown: заголовки, списки, таблицы, цитаты и блоки кода.
                    </small>
                </div>

                <div class="example-container">
                    <label for="task-example">Примеры задачи</label>
                    <textarea name="example" id="task-example"
                        placeholder="Примеры для задачи в Markdown...">{{ old('example', $isEdit ? $task->example : '') }}</textarea>
                    <small class="field-hint">
                        Например: `**Вход:** 2 3`, `**Выход:** 5` или fenced code block.
                    </small>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3>Настройки запуска решения</h3>
                        <span>Окружение и способ запуска кода</span>
                    </div>

                    <div class="settings-grid">
                        <div class="form-group">
                            <label for="execution-environment">Окружение выполнения</label>
                            <select name="execution_environment_id" id="execution-environment">
                                @forelse($environments as $environment)
                                    <option value="{{ $environment->id }}" @selected(old('execution_environment_id', $isEdit ? $task->environment_id : null) == $environment->id)>
                                        {{ $environment->name }} — {{ $environment->docker_image_name }}
                                    </option>
                                @empty
                                    <option value="" disabled>Нет активных окружений</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="time-limit">Лимит времени, сек.</label>
                            <input type="number" name="time_limit_s" id="time-limit" min="0.1" max="30" step="0.1"
                                value="{{ old('time_limit_s', $isEdit ? $task->time_limit_s : 2) }}">
                        </div>

                        <div class="form-group">
                            <label for="memory-limit">Лимит памяти, МБ</label>
                            <input type="number" name="memory_limit_mb" id="memory-limit" min="64" max="2048"
                                value="{{ old('memory_limit_mb', $isEdit ? $task->memory_limit_mb : 128) }}">
                        </div>

                        <div class="form-group">
                            <label for="input-mode">Ввод данных</label>
                            <select name="input_mode" id="input-mode">
                                <option value="stdin">Через стандартный ввод</option>
                                <option value="files">Через файлы</option>
                                <option value="stdin_and_files">Стандартный ввод + файлы</option>
                            </select>
                        </div>
                    </div>

                    <div class="mode-options">
                        <label class="mode-option {{ $runnerMode === 'solution' ? 'active' : '' }}" id="solution-mode-option">
                            <input type="radio" name="runner_mode" value="solution" @checked($runnerMode === 'solution')>
                            <div>
                                <strong>Запускать код пользователя</strong>
                                <small>Система запускает solution.py напрямую.</small>
                            </div>
                        </label>

                        <label class="mode-option {{ $runnerMode === 'runner' ? 'active' : '' }}" id="runner-mode-option">
                            <input type="radio" name="runner_mode" value="runner" @checked($runnerMode === 'runner')>
                            <div>
                                <strong>Использовать runner.py</strong>
                                <small>Нужно для задач на функцию или сложного запуска.</small>
                            </div>
                        </label>
                    </div>

                    <div class="form-group" id="runner-file-block" style="display: none;">
                        <label for="runner-file">Файл runner.py</label>
                        <input type="file" name="runner_file" id="runner-file" accept=".py">
                        <small class="field-hint">
                            @if($isEdit && $task->runner_file_path)
                                Сейчас используется: {{ basename($task->runner_file_path) }}. Загрузите новый файл, чтобы заменить его.
                            @else
                            Runner будет запускаться вместо solution.py. Например, он может импортировать функцию из
                            решения пользователя.
                            @endif
                        </small>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3>Настройки проверки ответа</h3>
                        <span>Стандартный checker или свой checker.py</span>
                    </div>

                    <div class="mode-options">
                        <label class="mode-option {{ $checkerType === 'standard' ? 'active' : '' }}" id="standard-checker-option">
                            <input type="radio" name="checker_type" value="standard" @checked($checkerType === 'standard')>
                            <div>
                                <strong>Стандартный checker</strong>
                                <small>Сравнивает expected и output по токенам.</small>
                            </div>
                        </label>

                        <label class="mode-option {{ $checkerType === 'custom' ? 'active' : '' }}" id="custom-checker-option">
                            <input type="radio" name="checker_type" value="custom" @checked($checkerType === 'custom')>
                            <div>
                                <strong>Custom checker.py</strong>
                                <small>Для нескольких правильных ответов, float, JSON, CSV и сложной логики.</small>
                            </div>
                        </label>
                    </div>

                    <div class="settings-grid">
                        <div class="form-group">
                            <label for="checker-time-limit">Лимит checker-а, сек.</label>
                            <input type="number" name="checker_time_limit_s" id="checker-time-limit" min="1" max="10"
                                value="2">
                        </div>
                    </div>

                    <div class="form-group" id="checker-file-block" style="display: none;">
                        <label for="checker-file">Файл checker.py</label>
                        <input type="file" name="checker_file" id="checker-file" accept=".py">
                        <small class="field-hint">
                            @if($isEdit && $task->checker_file_path)
                                Сейчас используется: {{ basename($task->checker_file_path) }}. Загрузите новый файл, чтобы заменить его.
                            @else
                            Checker получает input, expected и output. Код 0 — Accepted, код 1 — Wrong Answer.
                            @endif
                        </small>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3>Тесты и файлы задачи</h3>
                        <span>Все тесты можно описать одним JSON</span>
                    </div>

                    <div class="settings-grid">
                        <div class="form-group">
                            <label for="tests-json-file">Файл tests.json</label>
                            <input type="file" name="tests_json_file" id="tests-json-file" accept=".json">
                            <small class="field-hint">
                                JSON описывает входные данные, expected, публичность тестов и подключаемые файлы.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="task-files">Файлы задачи</label>
                            <input type="file" name="task_files[]" id="task-files" multiple>
                            <small class="field-hint">
                                Публичные файлы будут видны пользователю в условии задачи и доступны для скачивания.
                                Private-файлы попадут только в контейнер при запуске тестов.
                            </small>
                            <div id="task-files-visibility" class="file-visibility-list"></div>
                        </div>
                    </div>

                    @if($isEdit && $task->files->isNotEmpty())
                        <div class="form-group">
                            <label>Загруженные файлы</label>
                            <div class="file-visibility-list existing-files-list">
                                @foreach($task->files as $taskFile)
                                    <div class="file-visibility-item">
                                        <div>
                                            <strong>{{ basename($taskFile->file_path) }}</strong>
                                            <span>{{ $taskFile->is_public ? 'виден пользователю' : 'только для контейнера' }}</span>
                                        </div>
                                        <select name="existing_task_files_visibility[{{ $taskFile->id }}]">
                                            <option value="public" @selected($taskFile->is_public)>Публичный</option>
                                            <option value="private" @selected(!$taskFile->is_public)>Private</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="tests-json-content">Содержимое tests.json</label>
                        <textarea name="tests_json_content" id="tests-json-content" class="code-textarea">{{ $testsJsonValue }}</textarea>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3>Авторское решение</h3>
                        <span>{{ $isEdit ? 'Изменения сохраняются только после успешной проверки' : 'Задача создаётся только после успешной проверки' }}</span>
                    </div>

                    <div class="form-group">
                        <label for="starter-code">Стартовый код для пользователя</label>
                        <textarea name="starter_code" id="starter-code" class="code-textarea"
                            placeholder="Код, который будет открыт в редакторе у пользователя...">{{ old('starter_code', $isEdit ? $task->starter_code : '') }}</textarea>
                        <small class="field-hint">
                            Например: импорт библиотек, сигнатура функции или чтение файла из files/.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="reference-solution">Код reference_solution.py</label>
                        <textarea name="reference_solution" id="reference-solution" class="code-textarea"
                            placeholder="Введите авторское решение...">{{ old('reference_solution', $isEdit ? $task->reference_solution : '') }}</textarea>
                        <small class="field-hint">
                            Система прогонит это решение на всех тестах. Если оно не проходит, задача не будет создана.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="reference-solution-file">Или загрузить reference_solution.py</label>
                        <input type="file" name="reference_solution_file" id="reference-solution-file" accept=".py">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.tasks.show') }}" class="btn btn-secondary" style="text-decoration:none;">Отмена</a>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Сохранить изменения' : 'Сохранить задачу' }}</button>
                </div>

            </form>
        </div>

        <div class="preview-section">
            <h2 class="section-title">Превью задачи</h2>

            <div class="task-card" style="padding: 25px;">
                <div class="task-header">
                    <div>
                        <span class="task-category" id="preview-category">массивы, два указателя</span>
                        <h1 class="task-title" id="preview-title">Two Sum</h1>
                    </div>
                    <span class="task-difficulty easy" id="preview-difficulty"></span>
                </div>

                <div class="task-description markdown-content" id="preview-description">
                    <p>Напишите функцию <code>two_sum</code>, которая принимает список чисел и целевое число.
                        Функция
                        должна вернуть индексы двух чисел, которые в сумме дают целевое значение.</p>
                    <p>Можно предположить, что существует ровно одно решение, и нельзя использовать один и тот же
                        элемент дважды.</p>
                </div>

                <div class="task-example">
                    <div class="task-example-title">Примеры</div>
                    <div class="task-example-content markdown-content" id="preview-task-example"></div>
                </div>

                <div class="preview-block">
                    <div class="preview-block-title">Настройки проверки</div>

                    <div class="settings-list">
                        <div class="setting-item">
                            <span>Окружение</span>
                            <span id="preview-environment">Python 3.12 Basic</span>
                        </div>

                        <div class="setting-item">
                            <span>Запуск</span>
                            <span id="preview-entrypoint">solution.py</span>
                        </div>

                        <div class="setting-item">
                            <span>Ввод</span>
                            <span id="preview-input-mode">stdin</span>
                        </div>

                        <div class="setting-item">
                            <span>Checker</span>
                            <span id="preview-checker">standard tokens</span>
                        </div>

                        <div class="setting-item">
                            <span>Лимиты</span>
                            <span id="preview-limits">2 сек. / 128 МБ</span>
                        </div>
                    </div>
                </div>

                <div class="preview-block">
                    <div class="preview-block-title">Pipeline проверки</div>

                    <div class="pipeline">
                        <div class="pipeline-item">
                            <span class="pipeline-number">1</span>
                            <div class="pipeline-text">
                                <strong>Собирается workspace</strong>
                                <span>Код пользователя, runner при необходимости, input и файлы из tests.json.</span>
                            </div>
                        </div>

                        <div class="pipeline-item">
                            <span class="pipeline-number">2</span>
                            <div class="pipeline-text">
                                <strong>Запускается решение</strong>
                                <span id="preview-run-command">timeout 2s python solution.py &lt; input.txt →
                                    output.txt</span>
                            </div>
                        </div>

                        <div class="pipeline-item">
                            <span class="pipeline-number">3</span>
                            <div class="pipeline-text">
                                <strong>Проверяется output.txt</strong>
                                <span id="preview-checker-text">Стандартный checker сравнивает expected и output по
                                    токенам.</span>
                            </div>
                        </div>

                        <div class="pipeline-item">
                            <span class="pipeline-number">4</span>
                            <div class="pipeline-text">
                                <strong>Создание задачи</strong>
                            <span>{{ $isEdit ? 'Изменения сохраняются только после успешного прогона авторского решения.' : 'Задача создаётся только после успешного прогона авторского решения.' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/admin/task/task-create.js'])
</body>

</html>
