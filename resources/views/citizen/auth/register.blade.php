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
            <div class="citizen-auth-topbar">
                <a href="{{ route('citizen.login') }}" class="citizen-auth-back">← <span>Back to sign in</span></a>
                <button type="button" class="citizen-theme-toggle" data-theme-toggle aria-label="Switch to light mode">
                    <svg data-theme-icon="moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    <svg data-theme-icon="sun" class="hidden" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l-1.41 1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>
            </div>
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
