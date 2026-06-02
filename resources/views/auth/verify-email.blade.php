<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Подтверждение почты — Code master</title>
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
                <span class="auth-kicker">Проверка аккаунта</span>
                <h1>Подтвердите вашу почту</h1>
                <p>Мы отправили ссылку активации на указанный email. Проверьте почту и нажмите на ссылку, чтобы продолжить.</p>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <span>Проверка электронной почты</span>
                <h2>Подтвердите аккаунт</h2>
                <p>Если письмо не пришло, вы можете отправить его повторно.</p>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="auth-status">Новая ссылка подтверждения отправлена.</div>
            @endif

            <div class="auth-card-note">
                <p>Ссылка на подтверждение отправлена на <strong>{{ auth()->user()->email }}</strong>. Проверьте, пожалуйста, входящие и папку «Спам».</p>
            </div>

            <div class="auth-form">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <button type="submit" class="submit-button">Отправить ссылку повторно</button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="submit-button secondary-button">Выйти</button>
                </form>
            </div>
        </section>
    </main>
</body>

</html>
