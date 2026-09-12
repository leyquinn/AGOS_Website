<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Operations' }} - AGOS</title>
    <script>if (localStorage.getItem('agos-theme') === 'light') document.documentElement.classList.add('theme-light');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#031027] text-slate-100 font-sans antialiased" onload="document.documentElement.classList.contains('theme-light') && document.body.classList.add('theme-light')">
    <div class="min-h-screen lg:flex">
        <x-sidebar />
        <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>
        <div class="min-w-0 flex-1">
            <x-navbar :title="$title ?? 'Operations'" />
            <main class="portal-main min-h-[calc(100vh-5rem)] bg-[#061633] px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                <div class="portal-content w-full">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
