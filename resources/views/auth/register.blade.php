<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Register</title>

    @vite(['resources/css/auth/login.css'])
</head>

<body>
    <div class="auth-container">
        @if (session('status'))
                <div class="auth-status">
            {{ session('status') }}
        </div> @endif <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf
        <div class="form-group">
            <label for="name" class="form-label">Name</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus
                autocomplete="name">
            @if ($errors->has('name'))
                        <div class="form-error">{{ $errors->first('name') }}
                </div>
            @endif
        </div> <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required
            autocomplete="email">
        @if ($errors->has('email'))
                    <div class="form-error">{{ $errors->first('email') }}
            </div>
        @endif
    </div> <div class="form-group">
    <label for="password" class="form-label">Password</label>
    <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password">
    @if ($errors->has('password'))
        <div class="form-error">{{ $errors->first('password') }}</div>
    @endif
    </div> <div class="form-group">
    <label for="password_confirmation" class="form-label">Confirm Password</label>
    <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required
        autocomplete="new-password">
    @if ($errors->has('password_confirmation'))
        <div class="form-error">{{ $errors->first('password_confirmation') }}</div>
    @endif
    </div> <div class="form-footer">
    <a class="auth-link" href="{{ route('login') }}">
        Already registered?
    </a>

    <button type="submit" class="submit-button">
        Register
    </button>
    </div>
    </form>
    </div>
</body>

</html>
