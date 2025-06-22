<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Задача не найдена</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet" />

    @vite(['resources/css/task/task-not-found.css'])
</head>

<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">Задача не найдена</h1>
            <p class="error-message">
                Запрошенная вами задача не существует или была удалена.<br>
                Пожалуйста, проверьте правильность URL-адреса или вернитесь на главную страницу.
            </p>
            <div class="error-actions">
                <a href="{{ route('home') }}" class="btn btn-primary">На главную</a>
            </div>
        </div>
    </div>
</body>

</html>
