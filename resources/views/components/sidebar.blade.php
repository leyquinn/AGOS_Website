<aside data-sidebar class="portal-sidebar fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-blue-300/10 bg-[#062653] shadow-2xl shadow-slate-950/30 transition-transform duration-300 lg:static lg:min-h-screen lg:w-64 lg:translate-x-0 lg:shadow-none">
    <div class="flex h-full flex-col overflow-y-auto px-3 py-4 sm:px-4 sm:py-5">
        <div class="mb-9 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="sidebar-brand flex min-w-0 items-center gap-3 rounded-xl px-2 py-1.5 transition hover:bg-blue-900/50" aria-label="AGOS Dashboard">
                <span class="flex h-9 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[#031b3c] p-1.5"><img src="{{ asset('images/agos-logo.png') }}" alt="AGOS" class="h-full w-full object-contain"></span>
                <span class="sidebar-label hidden min-w-0 text-xs font-bold tracking-[0.16em] text-blue-100 xl:block">AGOS<span class="block truncate text-[8px] font-medium tracking-[0.08em] text-blue-300/60">FLOOD INTELLIGENCE</span></span>
            </a>
            <button type="button" data-sidebar-toggle class="hidden rounded-lg p-2 text-blue-200 transition hover:bg-blue-900/60 lg:block" aria-label="Collapse sidebar" title="Collapse sidebar">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"></path></svg>
            </button>
            <button type="button" data-sidebar-close class="rounded-lg p-2 text-blue-100 transition hover:bg-blue-900 lg:hidden" aria-label="Close navigation">×</button>
        </div>
        <p class="sidebar-label mb-3 px-3 text-[9px] font-bold uppercase tracking-[0.2em] text-blue-200/45">Overview</p>

        <nav class="space-y-1.5" aria-label="Portal navigation">
            @php
                $items = [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '▦'],
                    ['route' => 'operations', 'label' => 'Operations', 'icon' => '◈'],
                    ['route' => 'reports', 'label' => 'Reports', 'icon' => '▥'],
                    ['route' => 'harvesting', 'label' => 'Sources & Harvesting', 'icon' => '▤'],
                    ['route' => 'analytics', 'label' => 'Model Analytics', 'icon' => '⌁'],
                    ['route' => 'settings', 'label' => 'Settings', 'icon' => '⚙'],
                ];
            @endphp
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" class="sidebar-nav-item group relative flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['route']) ? 'border-blue-300/20 bg-blue-500/20 text-white' : 'border-transparent text-blue-100/70 hover:bg-blue-900/60 hover:text-white' }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif>
                    <span class="sidebar-nav-icon flex h-8 w-8 items-center justify-center rounded-lg bg-[#031b3c] text-sm text-blue-200 transition group-hover:bg-blue-800/60 group-hover:text-white">{{ $item['icon'] }}</span>
                    <span class="sidebar-label flex-1">{{ $item['label'] }}</span>
                    @if (request()->routeIs($item['route']))<span class="h-1.5 w-1.5 rounded-full bg-white shadow-lg shadow-white/60"></span>@endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-blue-300/15 pt-4">
            <p class="sidebar-label mb-2 px-3 text-[9px] font-medium uppercase tracking-[0.18em] text-blue-200/40">Account</p>
            <button type="button" data-logout class="group flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-medium text-blue-100/70 transition hover:bg-red-500/10 hover:text-red-200" aria-label="Log out" title="Log out">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-950/50 text-blue-200 transition group-hover:bg-red-500/15 group-hover:text-red-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 17l5-5-5-5m5 5H3m8-9V3a2 2 0 012-2h6a2 2 0 012 2v18a2 2 0 01-2 2h-6a2 2 0 01-2-2v-1"></path></svg>
                </span>
                <span class="sidebar-label flex-1 text-left">Log out</span>
            </button>
        </div>
    </div>
</aside>