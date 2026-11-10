<header class="portal-header border-b border-slate-200 bg-white/95 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/95">
    <div class="flex min-h-20 items-center justify-between gap-4 px-5 sm:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" data-sidebar-open class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60 lg:hidden" aria-label="Open navigation">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $title ?? 'Operations' }}</h1>
                <p class="mt-1 truncate text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Davao City • Last sync 10:17:03 • Auto refresh 60s</p>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <div class="relative">
                <button type="button" data-notifications-toggle class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60" aria-label="Notifications" title="Notifications" aria-expanded="false" aria-controls="notifications-menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-6 13a2.5 2.5 0 0 0 2.5-2.5h-5A2.5 2.5 0 0 0 12 22Z"></path></svg>
                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-950"></span>
                </button>
                <div id="notifications-menu" data-notifications-menu class="notifications-menu absolute right-0 top-12 z-50 hidden w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-950/15 dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-center justify-between"><h2 class="text-xs font-bold text-slate-900 dark:text-white">Notifications</h2><button type="button" data-notifications-clear class="rounded-full bg-emerald-500/15 px-2 py-1 text-[9px] font-bold text-emerald-600 transition-colors hover:bg-emerald-500/25 dark:text-emerald-300">All clear</button></div>
                    <div data-notifications-list class="mt-3 space-y-2"><div class="notification-item rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800"><p class="notification-item-title text-[10px] font-bold text-slate-700 dark:text-slate-200">Pipeline operating normally</p><p class="notification-item-message mt-1 text-[10px] leading-relaxed text-slate-500 dark:text-slate-400">No new alerts require your attention.</p></div></div>
                </div>
            </div>
            <a href="{{ route('settings') }}" class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60" aria-label="Settings" title="Settings">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.6 3.2 10.3 2h3.4l.7 1.2 1.4.6 1.4-.2 2.4 2.4-.2 1.4.6 1.4 1.2.7v3.4l-1.2.7-.6 1.4.2 1.4-2.4 2.4-1.4-.2-1.4.6-.7 1.2h-3.4l-.7-1.2-1.4-.6-1.4.2-2.4-2.4.2-1.4-.6-1.4L2.8 13V9.6L4 8.9l.6-1.4-.2-1.4 2.4-2.4 1.4.2 1.4-.7Z"></path><circle cx="12" cy="11.3" r="3"></circle></svg>
            </a>
            <button type="button" data-theme-toggle class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60" aria-label="Switch to light mode">
                <svg data-theme-icon="moon" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"></path></svg>
                <svg data-theme-icon="sun" class="hidden h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
            </button>
        </div>
    </div>
</header>
