<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGOS - Multi-Task NLP Framework for Flood Validation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#031027] text-slate-100 font-sans antialiased selection:bg-blue-600 selection:text-white min-h-screen">

    <!-- Sticky Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#031027]/90 backdrop-blur-md border-b border-blue-950/50">
        <div class="max-w-7xl mx-auto px-8 h-20 flex items-center justify-between">
            <a href="#home" class="text-2xl font-black tracking-widest text-white">AGOS</a>
            
            <nav class="hidden md:flex items-center space-x-10 text-sm font-medium text-slate-300">
                <a href="#home" class="hover:text-blue-400 transition-colors">Home</a>
                <a href="#problem" class="hover:text-blue-400 transition-colors">Problem</a>
                <a href="#process" class="hover:text-blue-400 transition-colors">Process</a>
                <a href="#features" class="hover:text-blue-400 transition-colors">Features</a>
                <a href="#results" class="hover:text-blue-400 transition-colors">Results</a>
                <a href="#team" class="hover:text-blue-400 transition-colors">Team</a>
            </nav>

            <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-7 py-2.5 rounded-full transition-all shadow-md shadow-blue-600/30">
                Sign In
            </a>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="py-12 bg-[#020b1a] border-t border-blue-950/60 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <div class="text-lg font-black tracking-widest text-white mb-1">AGOS</div>
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