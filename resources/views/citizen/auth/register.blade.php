<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Citizen Account - AGOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="citizen-body">
    <main class="citizen-auth-page">
        <section class="citizen-auth-card">
            <a href="{{ route('citizen.login') }}" class="citizen-auth-back">← <span>Back to sign in</span></a>
            <div class="citizen-auth-brand"><img src="{{ asset('images/agos-logo.png') }}" alt="AGOS"><span>Citizen App</span></div>
            <h1>Create your account</h1>
            <p class="citizen-auth-lead">Register first to report flooding and receive local alerts.</p>

            @if ($errors->any())
                <div class="citizen-auth-message citizen-auth-error" role="alert">Please review the highlighted fields and try again.</div>
            @endif

            <form action="{{ route('citizen.register.store') }}" method="POST" class="citizen-auth-form">
                @csrf
                <label>Full name<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required placeholder="Juan Dela Cruz"></label>
                @error('name')<small>{{ $message }}</small>@enderror
                <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="you@example.com"></label>
                @error('email')<small>{{ $message }}</small>@enderror
                <label>Password<input type="password" name="password" autocomplete="new-password" required placeholder="At least 8 characters"></label>
                @error('password')<small>{{ $message }}</small>@enderror
                <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" required placeholder="Repeat your password"></label>
                <button type="submit">Create Citizen Account</button>
            </form>

            <p class="citizen-auth-switch">Already registered? <a href="{{ route('citizen.login') }}">Sign in</a></p>
        </section>
    </main>
</body>
</html>
