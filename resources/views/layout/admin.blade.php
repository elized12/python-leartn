<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Админ-панель</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet">

    @vite(['resources/css/layout/admin.css', 'resources/js/echo.js', 'resources/js/admin/realtime.js'])
    @stack('css')
</head>

<body>
    <div class="admin-container">
        @include('layout.partial.admin.sidebar')

        <div class="main-content">
            <div class="header">
                <h1>@yield('title')</h1>
                <div class="user-menu">
                    <span>{{ Auth::user()->name }}</span>
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                </div>
            </div>

            @yield('content')
        </div>
    </div>

    @stack('js')
</body>

</html>
