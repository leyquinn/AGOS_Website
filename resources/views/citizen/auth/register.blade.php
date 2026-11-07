<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Citizen Account - AGOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="citizen-body citizen-auth-body">
    <main class="citizen-auth-page">
        <section class="citizen-auth-card">
            <div class="citizen-auth-topbar">
                <a href="{{ route('landing') }}" class="citizen-auth-back citizen-auth-exit" aria-label="Exit citizen registration" title="Back to landing page">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
                </a>
                <button type="button" class="citizen-theme-toggle" data-theme-toggle aria-label="Switch to light mode">
                    <svg data-theme-icon="moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    <svg data-theme-icon="sun" class="hidden" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l-1.41 1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>
            </div>
            <h1 class="citizen-auth-heading">Create your account</h1>
            <p class="citizen-auth-lead">Register first to report flooding and receive local alerts.</p>

            @if ($errors->any())
                <div class="citizen-auth-message citizen-auth-error" role="alert">Please review the highlighted fields and try again.</div>
            @endif

            <form action="{{ route('citizen.register.store') }}" method="POST" class="citizen-auth-form">
                @csrf
                <label>Full name<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required placeholder="Your full name"></label>
                @error('name')<small>{{ $message }}</small>@enderror
                <label>
                    Email address <span class="citizen-auth-required">(required)</span>
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="you@example.com">
                    <small class="citizen-auth-help">We will send your account approval or decline notification to this email.</small>
                </label>
                @error('email')<small>{{ $message }}</small>@enderror
                <label>Assigned barangay
                    <select name="barangay_id" required>
                        <option value="">Select your barangay</option>
                        @forelse ($barangays as $barangay)
                            <option value="{{ $barangay->id }}" @selected(old('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
                        @empty
                            <option value="" disabled>No barangays are currently available</option>
                        @endforelse
                    </select>
                </label>
                @error('barangay_id')<small>{{ $message }}</small>@enderror
                <label>Password
                    <span class="citizen-password-field">
                        <input id="citizen-register-password" type="password" name="password" autocomplete="new-password" required placeholder="At least 8 characters">
                        <button type="button" data-citizen-password-toggle="citizen-register-password" aria-label="Show password" title="Show password">
                            <svg data-password-icon="show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M2.04 12.35a1 1 0 010-.7C3.45 7.7 7.27 5 12 5s8.55 2.7 9.96 6.65a1 1 0 010 .7C20.55 16.3 16.73 19 12 19s-8.55-2.7-9.96-6.65z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg data-password-icon="hide" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 3l18 18M10.58 10.58a2 2 0 002.84 2.84M9.88 5.09A10.7 10.7 0 0112 5c4.73 0 8.55 2.7 9.96 6.65a1 1 0 01-.06.85m-4.1 3.74A10.7 10.7 0 0112 19c-4.73 0-8.55-2.7-9.96-6.65a1 1 0 01.06-.85 10.7 10.7 0 013.38-4.38"></path></svg>
                        </button>
                    </span>
                </label>
                @error('password')<small>{{ $message }}</small>@enderror
                <label>Confirm password
                    <span class="citizen-password-field">
                        <input id="citizen-register-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" required placeholder="Repeat your password">
                        <button type="button" data-citizen-password-toggle="citizen-register-password-confirmation" aria-label="Show password" title="Show password">
                            <svg data-password-icon="show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M2.04 12.35a1 1 0 010-.7C3.45 7.7 7.27 5 12 5s8.55 2.7 9.96 6.65a1 1 0 010 .7C20.55 16.3 16.73 19 12 19s-8.55-2.7-9.96-6.65a1 1 0 010-.7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg data-password-icon="hide" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 3l18 18M10.58 10.58a2 2 0 002.84 2.84M9.88 5.09A10.7 10.7 0 0112 5c4.73 0 8.55 2.7 9.96 6.65a1 1 0 01-.06.85m-4.1 3.74A10.7 10.7 0 0112 19c-4.73 0-8.55-2.7-9.96-6.65a1 1 0 01.06-.85 10.7 10.7 0 013.38-4.38"></path></svg>
                        </button>
                    </span>
                </label>
                <button type="submit">Create Citizen Account</button>
            </form>

            <p class="citizen-auth-switch">Already registered? <a href="{{ route('citizen.login') }}">Sign in</a></p>
        </section>
    </main>
</body>
<script>
    document.querySelectorAll('[data-citizen-password-toggle]').forEach((toggle) => {
        const input = document.getElementById(toggle.dataset.citizenPasswordToggle);
        toggle.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            toggle.setAttribute('title', isVisible ? 'Show password' : 'Hide password');
            toggle.querySelector('[data-password-icon="show"]').classList.toggle('hidden', !isVisible);
            toggle.querySelector('[data-password-icon="hide"]').classList.toggle('hidden', isVisible);
        });
    });
</script>
</html>
