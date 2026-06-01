<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Регистрация — Code master</title>
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
                <span class="auth-kicker">Начните обучение</span>
                <h1>Создайте аккаунт и собирайте свой прогресс.</h1>
                <p>После регистрации откроются курсы, задачи, история решений и рейтинг учеников.</p>
            </div>
            <div class="auth-benefits">
                <div><strong>Практика</strong><span>Задачи с автоматической проверкой</span></div>
                <div><strong>Подсказки</strong><span>ИИ помогает разобрать ошибки</span></div>
                <div><strong>Профиль</strong><span>Курсы, решения и достижения в одном месте</span></div>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <span>Новый аккаунт</span>
                <h2>Регистрация</h2>
                <p>Заполните данные, чтобы начать решать задачи.</p>
            </div>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="name" class="form-label">Имя</label>
                    <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required
                        autofocus autocomplete="name" placeholder="Как вас зовут?">
                    @if ($errors->has('name'))
                        <div class="form-error">{{ $errors->first('name') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required
                        autocomplete="email" placeholder="you@example.com">
                    @if ($errors->has('email'))
                        <div class="form-error">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Пароль</label>
                    <input id="password" class="form-input" type="password" name="password" required
                        autocomplete="new-password" placeholder="Минимум 8 символов">
                    @if ($errors->has('password'))
                        <div class="form-error">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Повторите пароль</label>
                    <input id="password_confirmation" class="form-input" type="password" name="password_confirmation"
                        required autocomplete="new-password" placeholder="Еще раз пароль">
                    @if ($errors->has('password_confirmation'))
                        <div class="form-error">{{ $errors->first('password_confirmation') }}</div>
                    @endif
                </div>

                <button type="submit" class="submit-button">Создать аккаунт</button>

                <p class="auth-switch">
                    Уже есть аккаунт?
                    <a href="{{ route('login') }}">Войти</a>
                </p>
            </form>
        </section>
    </main>
</body>

</html>
