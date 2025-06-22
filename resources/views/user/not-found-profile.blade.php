<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Пользователь не найден | CodeMaster</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|fira-code:400" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/user/not-found-profile.css'])

</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="{{route('home')}}" class="logo">
                    <i class="fas fa-code"></i>
                    <span>code master</span>
                </a>
                <ul class="nav-links">
                    <li><a href="{{ route('home')}}"><i class="fas fa-list-ul"></i> Задачи</a></li>
                    <li><a href="{{ route('user.profile', ['userId' => auth()->user()->id])}}" class="active"><i
                                class="fas fa-user"></i> Профиль</a></li>
                </ul>
            </div>
            <div class="nav-right">
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </div>
                <div class="user-profile">
                    <img src="https://via.placeholder.com/32" alt="Profile" class="profile-pic">
                    <span class="username">coder123</span>
                    <i class="fas fa-caret-down"></i>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-user-slash"></i>
            </div>
            <h1 class="error-title">Пользователь не найден</h1>
            <p class="error-message">
                Запрошенный вами профиль не существует или был удален.
                Проверьте правильность введенного имени пользователя или
                вернитесь на главную страницу.
            </p>
            <div class="action-buttons">
                <a href="{{route('home')}}" class="btn primary-btn">
                    <i class="fas fa-home"></i> На главную
                </a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            © 2023 CodeMaster. Все права защищены.
        </div>
    </footer>
</body>

</html>