<header class="portal-header border-b border-blue-950/60 bg-[#031027]/95 backdrop-blur-md">
    <div class="flex min-h-20 items-center justify-between gap-4 px-5 sm:px-8">
        <div class="flex items-center gap-3">
            <button type="button" data-sidebar-open class="rounded-xl border border-blue-800/60 bg-blue-950/50 p-2.5 text-blue-200 transition hover:bg-blue-900 lg:hidden" aria-label="Open navigation">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div>
            <h1 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ $title ?? 'Operations' }}</h1>
            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Davao City • Last sync 10:17:03 • Auto refresh 60s</p>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <div class="relative">
            <button type="button" data-notifications-toggle class="rounded-xl p-2 text-slate-300 transition hover:bg-blue-900/70 hover:text-white" aria-label="Notifications" title="Notifications" aria-expanded="false" aria-controls="notifications-menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"></path></svg>
                <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            </button>
            <div id="notifications-menu" data-notifications-menu class="absolute right-0 top-12 z-50 hidden w-80 rounded-2xl border border-blue-900/50 bg-[#071738] p-4 shadow-2xl shadow-slate-950/40">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-white">Notifications</h2>
                    <span class="rounded-full bg-emerald-500/15 px-2 py-1 text-[9px] font-bold text-emerald-300">All clear</span>
                </div>
                <div class="mt-3 rounded-xl border border-blue-900/50 bg-[#0d2b5b] p-3">
                    <p class="text-[10px] font-bold text-blue-200">Pipeline operating normally</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-slate-400">No new alerts require your attention.</p>
                </div>
            </div>
            </div>
            <a href="{{ route('settings') }}" class="rounded-xl p-2 text-slate-300 transition hover:bg-blue-900/70 hover:text-white" aria-label="Settings" title="Settings">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.4 15a1.8 1.8 0 0 0 .35 2l.05.05-1.8 1.8-.05-.05a1.8 1.8 0 0 0-2-.35 1.8 1.8 0 0 0-1.1 1.65v.1h-2.6v-.1a1.8 1.8 0 0 0-1.1-1.65 1.8 1.8 0 0 0-2 .35l-.05.05-1.8-1.8.05-.05a1.8 1.8 0 0 0 .35-2 1.8 1.8 0 0 0-1.65-1.1h-.1v-2.6h.1a1.8 1.8 0 0 0 1.65-1.1 1.8 1.8 0 0 0-.35-2l-.05-.05 1.8-1.8.05.05a1.8 1.8 0 0 0 2 .35A1.8 1.8 0 0 0 12.25 5v-.1h2.6V5a1.8 1.8 0 0 0 1.1 1.65 1.8 1.8 0 0 0 2-.35l.05-.05 1.8 1.8-.05.05a1.8 1.8 0 0 0-.35 2 1.8 1.8 0 0 0 1.65 1.1h.1v2.6h-.1a1.8 1.8 0 0 0-1.65 1.2Z"></path></svg>
            </a>
            <button type="button" data-theme-toggle class="rounded-xl p-2 text-blue-300 transition hover:bg-blue-900/60 hover:text-blue-100" aria-label="Switch to light mode">
                <svg data-theme-icon="moon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg>
                <svg data-theme-icon="sun" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path stroke-linecap="round" stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>
            </button>
        </div>
    </div>
</header>