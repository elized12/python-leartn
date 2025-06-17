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
            <div class="task-card">
                <div class="task-header">
                    <div>
                        <span class="task-category">Алгоритмы / Массивы</span>
                        <h1 class="task-title">Сумма двух чисел</h1>
                    </div>
                    <span class="task-difficulty easy">Начинающий</span>
                </div>

                <div class="task-description">
                    <p>Напишите функцию <code>two_sum</code>, которая принимает список чисел и целевое число. Функция
                        должна вернуть индексы двух чисел, которые в сумме дают целевое значение.</p>
                    <p>Можно предположить, что существует ровно одно решение, и нельзя использовать один и тот же
                        элемент дважды.</p>
                </div>

                <div class="io-example">
                    <div class="io-title">Пример 1:</div>
                    <p><strong>Вход:</strong> nums = [2,7,11,15], target = 9</p>
                    <p><strong>Выход:</strong> [0,1]</p>
                    <p><strong>Объяснение:</strong> nums[0] + nums[1] == 9 → [0, 1]</p>
                </div>

                <div class="io-example">
                    <div class="io-title">Пример 2:</div>
                    <p><strong>Вход:</strong> nums = [3,2,4], target = 6</p>
                    <p><strong>Выход:</strong> [1,2]</p>
                </div>

                <div class="io-example">
                    <div class="io-title">Ограничения:</div>
                    <ul>
                        <li>2 ≤ nums.length ≤ 10<sup>4</sup></li>
                        <li>-10<sup>9</sup> ≤ nums[i] ≤ 10<sup>9</sup></li>
                        <li>-10<sup>9</sup> ≤ target ≤ 10<sup>9</sup></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="editor-container">
            <div class="task-card">
                <h3>Редактор кода</h3>
                <div id="text-editor"></div>
                <button class="run-button">Запустить код</button>
                <div class="output-container">
                    <div class="io-title">Вывод:</div>
                    <div id="output"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/pyodide/v0.27.6/full/pyodide.js"></script>
    @vite(['resources/js/text-editor.js', 'resources/css/task/task.css'])
</body>

</html>