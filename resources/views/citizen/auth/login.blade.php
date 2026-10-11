<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Sign In - AGOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="citizen-body">
    <main class="citizen-auth-page">
        <section class="citizen-auth-card">
            <a href="{{ route('landing') }}" class="citizen-auth-back">← <span>Back to AGOS</span></a>
            <div class="citizen-auth-brand"><img src="{{ asset('images/agos-logo.png') }}" alt="AGOS"><span>Citizen App</span></div>
            <h1>Welcome back</h1>
            <p class="citizen-auth-lead">Sign in to continue to the AGOS Citizen App.</p>

            @if ($errors->any())
                <div class="citizen-auth-message citizen-auth-error" role="alert">Please check your email and password and try again.</div>
            @endif

            <div class="citizen-demo-account">
                <strong>Demo account for interface testing</strong>
                <span>Email: <b>citizen.demo@agos.test</b></span>
                <span>Password: <b>Citizen@12345</b></span>
                <small>Replace this account with registered users before production.</small>
            </div>

            <form action="{{ route('citizen.login.store') }}" method="POST" class="citizen-auth-form">
                @csrf
                <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="you@example.com"></label>
                @error('email')<small>{{ $message }}</small>@enderror
                <label>Password<input type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"></label>
                @error('password')<small>{{ $message }}</small>@enderror
                <label class="citizen-check"><input type="checkbox" name="remember"> Remember me</label>
                <button type="submit">Sign in to Citizen App</button>
            </form>

            <p class="citizen-auth-switch">New to AGOS? <a href="{{ route('citizen.register') }}">Create an account</a></p>
        </section>
    </main>
</body>
</html>
