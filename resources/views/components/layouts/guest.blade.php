<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGOS - Multi-Task NLP Framework for Flood Validation</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/agos-favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/agos-favicon.png') }}">
    <script>if (localStorage.getItem('agos-theme') === 'light') document.documentElement.classList.add('theme-light');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-[#031027] text-slate-100 font-sans antialiased selection:bg-blue-600 selection:text-white min-h-screen transition-colors duration-300" onload="document.documentElement.classList.contains('theme-light') && document.body.classList.add('theme-light')">

    <!-- Sticky Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#031027]/90 backdrop-blur-md border-b border-blue-950/50">
        <div class="w-full px-6 sm:px-8 lg:px-12 h-20 grid grid-cols-[1fr_auto_1fr] items-center">
            <a href="#home" class="flex items-center justify-self-start" aria-label="AGOS home">
                <img src="{{ asset('images/agos-logo.png') }}" alt="AGOS - Bantay Agos, Ligtas Barangay" class="agos-brand-logo h-10 w-auto max-w-none object-contain sm:h-12">
            </a>
            
            <nav class="hidden md:flex items-center justify-center space-x-8 text-sm font-medium text-slate-300 lg:space-x-10">
                <a href="#home" class="hover:text-blue-400 transition-colors">Home</a>
                <a href="#heatmap" class="hover:text-blue-400 transition-colors">Heatmap</a>
                <a href="#problem" class="hover:text-blue-400 transition-colors">Problem</a>
                <a href="#process" class="hover:text-blue-400 transition-colors">Process</a>
                <a href="#features" class="hover:text-blue-400 transition-colors">Features</a>
                <a href="#results" class="hover:text-blue-400 transition-colors">Results</a>
                <a href="#team" class="hover:text-blue-400 transition-colors">Team</a>
            </nav>

            <button type="button" data-mobile-menu class="rounded-xl border border-blue-800/50 bg-blue-950/40 p-2 text-blue-200 transition hover:border-blue-500 hover:bg-blue-600 hover:text-white md:hidden" aria-label="Open menu" aria-expanded="false">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center justify-self-end gap-3">
                <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-7 py-2.5 rounded-full transition-all shadow-md shadow-blue-600/30">
                    Sign In
                </a>
                <button type="button" data-theme-toggle class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:bg-blue-600 hover:text-white" aria-label="Switch to light mode">
                <svg data-theme-icon="moon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg>
                <svg data-theme-icon="sun" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path stroke-linecap="round" stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>
                </button>
            </div>
        </div>
        <nav data-mobile-menu-panel class="hidden border-t border-blue-900/50 bg-[#031027] px-6 py-4 md:hidden">
            <div class="grid gap-3 text-sm font-semibold text-slate-300">
                <a href="#home">Home</a><a href="#heatmap">Heatmap</a><a href="#problem">Problem</a><a href="#process">Process</a><a href="#features">Features</a><a href="#results">Results</a><a href="#team">Team</a>
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-blue-950/60 bg-[#020b1a] py-14 text-slate-500 transition-colors duration-300">
        <div class="mx-auto grid w-full max-w-7xl gap-10 px-6 sm:px-8 lg:grid-cols-[1.2fr_1.5fr_1fr] lg:px-12">
            <div>
                <img src="{{ asset('images/agos-logo.png') }}" alt="AGOS - Bantay Agos, Ligtas Barangay" class="agos-brand-logo h-10 w-auto object-contain">
                <p class="mt-4 max-w-xs text-xs uppercase leading-relaxed tracking-wider">Ateneo de Davao University<br>Computer Studies Cluster</p>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-400">Turning community reports into clearer, more actionable flood intelligence.</p>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-blue-400">Research citation</span>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-slate-400">Cuba, Embuscado, Futalan (2026). <em>A Multi-Task NLP Framework for Barangay-Level Validation of Crowdsourced Flood Reports.</em></p>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-blue-400">Quick access</span>
                <div class="mt-3 grid gap-3 text-sm font-medium">
                    <a href="{{ route('login') }}" class="text-slate-400 transition hover:text-white">LGU Staff Login &rarr;</a>
                    <a href="{{ route('citizen.login') }}" class="text-slate-400 transition hover:text-white">Citizen Reporting App &rarr;</a>
                    <a href="{{ asset('thesis_paper.pdf') }}" target="_blank" rel="noopener" class="text-slate-400 transition hover:text-white">Read the Thesis &rarr;</a>
                </div>
            </div>
        </div>
        <div class="mx-auto mt-12 w-full max-w-7xl border-t border-blue-950/60 px-6 pt-6 text-xs sm:px-8 lg:px-12">Copyright &copy; 2026 AGOS Intelligence Platform. All rights reserved.</div>
    </footer>

</body>
</html>