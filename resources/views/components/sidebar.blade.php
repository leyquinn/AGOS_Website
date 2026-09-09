<aside data-sidebar class="portal-sidebar fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-blue-300/10 bg-[#021f4f] shadow-2xl shadow-slate-950/30 transition-transform duration-300 lg:static lg:min-h-screen lg:w-64 lg:translate-x-0 lg:shadow-none">
    <div class="flex h-full flex-col overflow-y-auto px-4 py-5">
        <div class="mb-7 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-3 py-2 transition hover:bg-blue-900/50" aria-label="AGOS Dashboard">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full border border-blue-200/20 bg-[#062653] p-1 shadow-inner shadow-blue-300/10"><img src="{{ asset('images/agos-logo.png') }}" alt="AGOS" class="h-full w-full rounded-full object-contain"></span>
                <span class="sidebar-label hidden text-xs font-black tracking-[0.18em] text-blue-100 xl:block">AGOS<span class="block text-[8px] font-semibold tracking-[0.08em] text-blue-300/70">FLOOD INTELLIGENCE</span></span>
            </a>
            <button type="button" data-sidebar-toggle class="hidden rounded-lg p-2 text-blue-100 transition hover:bg-blue-900 lg:block" aria-label="Collapse sidebar" title="Collapse sidebar">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"></path></svg>
            </button>
            <button type="button" data-sidebar-close class="rounded-lg p-2 text-blue-100 transition hover:bg-blue-900 lg:hidden" aria-label="Close navigation">×</button>
        </div>
        <div class="sidebar-label mb-7 rounded-2xl border border-blue-300/15 bg-gradient-to-br from-blue-900/60 to-blue-950/20 px-4 py-4 shadow-inner shadow-blue-200/5">
            <div class="flex items-center justify-between">
                <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-blue-200/70">Control center</p>
                <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/50"></span>
            </div>
            <p class="mt-2 text-xs font-medium text-blue-50">Davao City Operations</p>
            <p class="mt-1 text-[10px] text-blue-200/60">System operational</p>
        </div>
        <p class="sidebar-label mb-3 px-3 text-[9px] font-bold uppercase tracking-[0.22em] text-blue-200/50">Flood Intelligence</p>

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
                <a href="{{ route($item['route']) }}" class="sidebar-nav-item group flex items-center gap-3 rounded-xl border px-3 py-3 text-sm font-medium transition {{ request()->routeIs($item['route']) ? 'border-blue-300/20 bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg shadow-blue-950/40' : 'border-transparent bg-[#062653]/55 text-blue-100/75 hover:border-blue-300/10 hover:bg-blue-900/70 hover:text-white' }}">
                    <span class="sidebar-nav-icon flex h-8 w-8 items-center justify-center rounded-lg bg-[#031b3c] text-sm text-blue-200 shadow-inner shadow-blue-200/5 transition group-hover:bg-blue-800/60 group-hover:text-white">{{ $item['icon'] }}</span>
                    <span class="sidebar-label flex-1">{{ $item['label'] }}</span>
                    @if (request()->routeIs($item['route']))<span class="h-1.5 w-1.5 rounded-full bg-white shadow-lg shadow-white/60"></span>@endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-blue-300/15 pt-5">
            <button type="button" data-logout class="group flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-3 text-sm font-semibold text-blue-100/70 transition hover:border-red-400/20 hover:bg-red-500/10 hover:text-red-200" aria-label="Log out" title="Log out">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-950/50 text-blue-200 transition group-hover:bg-red-500/15 group-hover:text-red-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 17l5-5-5-5m5 5H3m8-9V3a2 2 0 012-2h6a2 2 0 012 2v18a2 2 0 01-2 2h-6a2 2 0 01-2-2v-1"></path></svg>
                </span>
                <span class="sidebar-label flex-1 text-left">Log out</span>
            </button>
        </div>
    </div>
</aside>