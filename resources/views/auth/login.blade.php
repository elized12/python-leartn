<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход — Code master</title>
    @vite(['resources/css/auth/login.css'])
</head>

<body>
    <main class="auth-shell">
        <section class="auth-aside">
            <a href="{{ route('home') }}" class="auth-logo">
                <span><i class="fas fa-code"></i></span>
                Code master
            </a>
            <div>
                <span class="auth-kicker">Python learning platform</span>
                <h1>Возвращайтесь к задачам, курсам и прогрессу.</h1>
                <p>Решайте задачи, проходите уроки и следите за рейтингом среди других учеников.</p>
            </div>
            <div class="auth-benefits">
                <div><strong>Онлайн judge</strong><span>Проверка Python-решений по тестам</span></div>
                <div><strong>Курсы</strong><span>Уроки с прогрессом и практикой</span></div>
                <div><strong>Рейтинг</strong><span>Очки за решенные задачи</span></div>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <span>С возвращением</span>
                <h2>Войти в аккаунт</h2>
                <p>Введите почту и пароль, чтобы продолжить обучение.</p>
            </div>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required
                        autofocus autocomplete="username" placeholder="you@example.com">
                    @if ($errors->has('email'))
                        <div class="form-error">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Пароль</label>
                    <input id="password" class="form-input" type="password" name="password" required
                        autocomplete="current-password" placeholder="Введите пароль">
                    @if ($errors->has('password'))
                        <div class="form-error">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="form-options">
                    <label for="remember_me" class="remember-label">
                        <input id="remember_me" type="checkbox" class="remember-checkbox" name="remember">
                        <span>Запомнить меня</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="auth-link" href="{{ route('password.request') }}">Забыли пароль?</a>
                    @endif
                </div>

                <button type="submit" class="submit-button">Войти</button>

                <p class="auth-switch">
                    Нет аккаунта?
                    <a href="{{ route('register') }}">Зарегистрироваться</a>
                </p>
            </form>
        </section>
    </main>
</body>

</html>
