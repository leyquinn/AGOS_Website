<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AGOS</title>
    <script>if (localStorage.getItem('agos-theme') === 'light') document.documentElement.classList.add('theme-light');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-[#031027] text-slate-100 font-sans antialiased selection:bg-blue-600 selection:text-white" onload="document.documentElement.classList.contains('theme-light') && document.body.classList.add('theme-light')">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-8 sm:px-8 sm:py-12">
        <div class="pointer-events-none absolute -left-40 top-1/4 h-96 w-96 rounded-full bg-blue-600/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-40 bottom-1/4 h-[30rem] w-[30rem] rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(96,165,250,0.14)_1px,transparent_1px),linear-gradient(90deg,rgba(96,165,250,0.14)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <section class="relative w-full max-w-lg">
            <div class="mb-6 flex items-center justify-between">

                <div class="ml-auto flex items-center justify-end gap-2">
                    <button type="button" data-theme-toggle class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:text-blue-200" aria-label="Switch to light mode">
                        <svg data-theme-icon="moon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg>
                        <svg data-theme-icon="sun" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path stroke-linecap="round" stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>
                    </button>
                    <a href="{{ route('landing') }}" class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:text-blue-200" aria-label="Close login" title="Back to landing page">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
                    </a>
                </div>
            </div>

            <div class="rounded-3xl border border-blue-900/50 bg-[#071738]/65 p-6 shadow-2xl shadow-blue-950/30 backdrop-blur-sm sm:p-10">

                <div class="mb-10">
                    <span class="text-xs uppercase tracking-[0.2em] text-blue-400 font-bold">Secure access</span>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Welcome back.</h2>
                    <p class="mt-3 text-base leading-relaxed text-slate-400">Sign in to continue to the AGOS operations console.</p>
                </div>

                <form action="{{ route('operations') }}" method="GET" class="space-y-6">
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-200">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@organization.gov.ph"
                            class="w-full rounded-xl border border-blue-900/60 bg-[#071738]/70 px-4 py-4 text-base text-white placeholder-slate-500 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-slate-200">Password</label>
                            <a href="#" class="text-xs font-semibold text-blue-400 transition hover:text-blue-300">Forgot password?</a>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password"
                            class="w-full rounded-xl border border-blue-900/60 bg-[#071738]/70 px-4 py-4 text-base text-white placeholder-slate-500 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    </div>

                    <label class="flex items-center gap-3 text-sm text-slate-400">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-blue-800 bg-[#071738] text-blue-600 focus:ring-blue-500/30">
                        Remember me on this device
                    </label>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-4 text-base font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#031027]">
                        Sign In
                    </button>
                </form>

                <div class="mt-10 border-t border-blue-950/60 pt-6 text-center">
                    <p class="text-xs leading-relaxed text-slate-500">
                        Access is limited to authorized DRRMO and LGU personnel.
                        <a href="{{ route('landing') }}" class="font-semibold text-blue-400 hover:text-blue-300">Return to homepage</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>