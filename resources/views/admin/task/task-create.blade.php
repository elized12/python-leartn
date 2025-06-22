<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание задачи</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet">

    @vite(['resources/css/admin/task/task-create.css'])

</head>

<body>
    <div class="container">
        <div class="form-section">
            <div class="section-title-content"
                style="display: flex; align-items: start; justify-content:space-between;">
                <a class="add-test-case" style="margin-top:0; text-decoration: none;"
                    href="{{ route('admin.main.show') }}">Назад</a>
                <h2 class="section-title">Создание новой задачи</h2>
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

            <form action="{{ route('admin.task-create.create') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="task-title">Название задачи</label>
                    <input name="title" type="text" id="task-title" placeholder="Например: Two Sum">
                </div>

                <div class="form-group">
                    <label for="task-difficulty">Сложность</label>
                    <input name="rating" type="number" id="task-difficulty" min="0" max="5000" name="rating"
                        placeholder="800">
                </div>

                <div class="form-group">
                    <label for="task-description">Описание задачи</label>
                    <textarea name="description" id="task-description"
                        placeholder="Подробное описание задачи..."></textarea>
                </div>

                <div class="example-container">
                    <textarea name="example" id="task-example" placeholder="Примеры для задачи..."></textarea>
                </div>

                <div class="form-group">
                    <label>Тестовые примеры</label>

                    <div id="test-cases-container">
                        <div class="test-case" data-id="1">
                            <div class="test-header">
                                <h4>Пример #1</h4>
                                <button type="button" class="remove-test" disabled>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="form-group">
                                <label for="input-1">Входные данные</label>
                                <input type="text" id="input-1" class="test-input" value=""
                                    placeholder="Например: [2,7,11,15], 9" name="test-case-input-1" required>
                            </div>
                            <div class="form-group">
                                <label for="output-${testId}">Ожидаемый вывод</label>
                                <input type="text" id="output-1" class="test-output" value=""
                                    placeholder="Например: [0,1]" name="test-case-output-1" required>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="add-test-case" class="add-test-case">+ Добавить тестовый
                        пример</button>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить задачу</button>
                </div>

            </form>
        </div>

        <div class="preview-section">
            <h2 class="section-title">Превью задачи</h2>

            <div class="task-card" style="padding: 25px;">
                <div class="task-header">
                    <div>
                        <span class="task-category" id="preview-category">Алгоритмы / Массивы</span>
                        <h1 class="task-title" id="preview-title">Two Sum</h1>
                    </div>
                    <span class="task-difficulty easy" id="preview-difficulty"></span>
                </div>

                <div class="task-description" id="preview-description">
                    <p>Напишите функцию <code>two_sum</code>, которая принимает список чисел и целевое число.
                        Функция
                        должна вернуть индексы двух чисел, которые в сумме дают целевое значение.</p>
                    <p>Можно предположить, что существует ровно одно решение, и нельзя использовать один и тот же
                        элемент дважды.</p>
                </div>

                <div class="task-example" id="preview-task-example">
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/admin/task/task-create.js'])
</body>

</html>
