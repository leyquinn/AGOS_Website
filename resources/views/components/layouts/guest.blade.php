<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGOS - Multi-Task NLP Framework for Flood Validation</title>
    <script>if (localStorage.getItem('agos-theme') === 'light') document.documentElement.classList.add('theme-light');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#031027] text-slate-100 font-sans antialiased selection:bg-blue-600 selection:text-white min-h-screen" onload="document.documentElement.classList.contains('theme-light') && document.body.classList.add('theme-light')">

    <!-- Sticky Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#031027]/90 backdrop-blur-md border-b border-blue-950/50">
        <div class="max-w-7xl mx-auto px-8 h-20 grid grid-cols-[1fr_auto_1fr] items-center">
            <a href="#home" class="flex items-center justify-self-start" aria-label="AGOS home">
                <img src="{{ asset('images/agos-logo.png') }}" alt="AGOS - Bantay Agos, Ligtas Barangay" class="h-16 w-auto object-contain">
            </a>
            
            <nav class="hidden md:flex items-center justify-center space-x-10 text-sm font-medium text-slate-300">
                <a href="#home" class="hover:text-blue-400 transition-colors">Home</a>
                <a href="#problem" class="hover:text-blue-400 transition-colors">Problem</a>
                <a href="#process" class="hover:text-blue-400 transition-colors">Process</a>
                <a href="#features" class="hover:text-blue-400 transition-colors">Features</a>
                <a href="#results" class="hover:text-blue-400 transition-colors">Results</a>
                <a href="#team" class="hover:text-blue-400 transition-colors">Team</a>
            </nav>

            <div class="flex items-center justify-self-end gap-3">
                <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-7 py-2.5 rounded-full transition-all shadow-md shadow-blue-600/30">
                    Sign In
                </a>
                <button type="button" data-theme-toggle class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:text-blue-200" aria-label="Switch to light mode">
                <svg data-theme-icon="moon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg>
                <svg data-theme-icon="sun" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path stroke-linecap="round" stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="py-12 bg-[#020b1a] border-t border-blue-950/60 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <img src="{{ asset('images/agos-logo.png') }}" alt="AGOS - Bantay Agos, Ligtas Barangay" class="h-12 w-auto object-contain mb-2">
                <p class="uppercase tracking-wider">Ateneo de Davao University • Computer Studies Cluster</p>
            </div>
            <div class="max-w-md text-left md:text-right text-slate-400 leading-relaxed">
                Cuba, Embuscado, Futalan (2026). A Multi-Task NLP Framework for Barangay-Level Validation of Crowdsourced Flood Reports.
            </div>
            <div>
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white font-medium">Staff Login</a>
            </div>
        </div>
    </footer>

</body>
</html>