<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login</title>

    @vite(['resources/css/auth/login.css'])
</head>

<body>
    <div class="auth-container">
        @if (session('status'))
            <div class="auth-status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required
                    autofocus autocomplete="username">
                @if ($errors->has('email'))
                    <div class="form-error">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" class="form-input" type="password" name="password" required
                    autocomplete="current-password">
                @if ($errors->has('password'))
                    <div class="form-error">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <div class="form-remember">
                <label for="remember_me" class="remember-label">
                    <input id="remember_me" type="checkbox" class="remember-checkbox" name="remember">
                    <span>Remember me</span>
                </label>
            </div>

            <div class="form-footer">
                @if (Route::has('password.request'))
                    <a class="forgot-password" href="{{ route('password.request') }}">
                        Forgot your password?
                    </a>
                @endif

                <button type="submit" class="submit-button">
                    Log in
                </button>
                <a href="{{ route('register') }}" class="submit-button">
                    Register
                </a>
            </div>
        </form>
    </div>
</body>

</html>
