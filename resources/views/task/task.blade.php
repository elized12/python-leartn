<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Python Code Challenge</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <div class="task-section">
            <a class="add-test-case" style="margin-top:0;margin-bottom:15px; text-decoration: none;"
                href="{{route('home') }}">Назад</a>
            <div class="task-card">
                <div class="task-header">
                    <div>
                        <span class="task-category">Алгоритмы / Массивы</span>
                        <h1 class="task-title">{{ $task->title }}</h1>
                    </div>
                    @php
                        if ($task->rating < 1200) {
                            $levelTask = 'Начинающиий';
                            $classTask = 'easy';
                        } elseif (1200 <= $task->rating && $task->rating < 1800) {
                            $levelTask = 'Средний';
                            $classTask = 'medium';
                        } else {
                            $levelTask = 'Професионал';
                            $classTask = 'hard';
                        }
                    @endphp
                    <span class="task-difficulty {{ $classTask }}">
                        {{ $levelTask }}
                    </span>
                </div>

                <div class="task-description">
                    {!! $task->description !!}
                </div>

                <div class="task-example">
                    {!! $task->example !!}
                </div>
            </div>
        </div>

        <form action="{{route('task.attempt.send', ['taskId' => $task->id])}}" method="POST">
            @csrf
            <div class="text-editor-block task-card">
                <div class="editor-container">
                    <h3>Редактор кода</h3>
                    <div id="text-editor"></div>
                    <div id="spinner"></div>
                    <textarea name="code" id="text-editor-textarea"></textarea>
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

    <script>
        window.userId = '{{auth()->user()->id}}';
    </script>
    @vite(['resources/js/echo.js', 'resources/js/task/text-editor.js', 'resources/css/task/task.css'])
</body>

</html>
