<aside data-sidebar class="portal-sidebar relative fixed inset-y-0 left-0 z-40 w-72 -translate-x-full overflow-hidden border-r border-slate-200 bg-white shadow-2xl shadow-slate-950/20 transition-[width,transform,background-color] duration-300 ease-in-out dark:border-slate-800 dark:bg-slate-950 lg:static lg:min-h-screen lg:w-64 lg:translate-x-0 lg:shadow-none">
    <div class="flex h-full min-w-0 flex-col overflow-x-hidden overflow-y-auto px-3 py-4 sm:px-4 sm:py-5">
        <div class="sidebar-header mb-8 flex h-16 min-h-16 items-center justify-between gap-2">
            <a href="{{ route('dashboard') }}" class="sidebar-brand flex min-w-0 items-center gap-3 rounded-xl px-2 py-1 transition-colors duration-200 hover:bg-slate-100 dark:hover:bg-slate-900" aria-label="AGOS Dashboard">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-50 p-1.5 dark:bg-slate-900"><img src="{{ asset('images/agos-logo.png') }}" alt="AGOS" class="h-full w-full object-contain"></span>
                <span class="sidebar-label min-w-0 text-sm font-bold tracking-[0.16em] text-slate-900 dark:text-slate-100">AGOS<span class="block truncate text-[9px] font-medium tracking-[0.08em] text-slate-500 dark:text-slate-400">FLOOD INTELLIGENCE</span></span>
            </a>
            <button type="button" data-sidebar-toggle class="sidebar-collapse-toggle absolute right-3 top-7 z-30 hidden h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 shadow-sm transition-all duration-200 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-white lg:flex" aria-label="Collapse sidebar" title="Collapse sidebar">
                <svg class="h-4 w-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"></path></svg>
            </button>
            <button type="button" data-sidebar-close class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden" aria-label="Close navigation">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"></path></svg>
            </button>
        </div>
        <p class="sidebar-label mb-3 px-3 text-[9px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Overview</p>

        <nav class="space-y-1.5" aria-label="Portal navigation">
            @php
                $items = [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
                    ['route' => 'operations', 'label' => 'Operations', 'icon' => 'operations'],
                    ['route' => 'reports', 'label' => 'Reports', 'icon' => 'reports'],
                    ['route' => 'harvesting', 'label' => 'Sources & Harvesting', 'icon' => 'harvesting'],
                    ['route' => 'analytics', 'label' => 'Model Analytics', 'icon' => 'analytics'],
                ];
                if (auth()->user()?->isSuperAdmin()) {
                    $items[] = ['route' => 'admin.barangays', 'label' => 'Barangay Admins', 'icon' => 'users'];
                }
                if (auth()->user()?->isBarangayAdmin()) {
                    $items[] = ['route' => 'admin.citizen-approvals', 'label' => 'Citizen Approvals', 'icon' => 'check'];
                }
                $items[] = ['route' => 'settings', 'label' => 'Settings', 'icon' => 'settings'];
            @endphp
            @foreach ($items as $item)
                @php($isActive = request()->routeIs($item['route']))
                <a href="{{ route($item['route']) }}" class="sidebar-nav-item group relative flex h-11 min-h-11 items-center gap-3 rounded-xl border border-transparent px-3 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white {{ $isActive ? 'is-active border-slate-200 bg-slate-100 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white' : '' }}" @if($isActive) aria-current="page" @endif>
                    <span class="sidebar-nav-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition-all duration-200 group-hover:text-slate-900 dark:text-slate-400 dark:group-hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            @if ($item['icon'] === 'dashboard') <rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>
                            @elseif ($item['icon'] === 'operations') <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/>
                            @elseif ($item['icon'] === 'reports') <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h9l3 3v15H6V3Z"/><path stroke-linecap="round" d="M9 11h6M9 15h6M9 7h3"/>
                            @elseif ($item['icon'] === 'harvesting') <path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 0 4 4m-4-4L8 8M5 12h14"/>
                            @elseif ($item['icon'] === 'analytics') <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8v-6"/>
                            @elseif ($item['icon'] === 'settings') <path stroke-linecap="round" stroke-linejoin="round" d="M9.6 3.2 10.3 2h3.4l.7 1.2 1.4.6 1.4-.2 2.4 2.4-.2 1.4.6 1.4 1.2.7v3.4l-1.2.7-.6 1.4.2 1.4-2.4 2.4-1.4-.2-1.4.6-.7 1.2h-3.4l-.7-1.2-1.4-.6-1.4.2-2.4-2.4.2-1.4-.6-1.4L2.8 13V9.6L4 8.9l.6-1.4-.2-1.4 2.4-2.4 1.4.2 1.4-.7Z"/><circle cx="12" cy="11.3" r="3"/>
                            @elseif ($item['icon'] === 'users') <path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20m6-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm5-5a3 3 0 0 1 2 5.2M16 15h1a3 3 0 0 1 3 3v2"/>
                            @else <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                            @endif
                        </svg>
                    </span>
                    <span class="sidebar-label flex-1 truncate">{{ $item['label'] }}</span>
                    <span class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 z-50 ml-3 -translate-y-1/2 scale-95 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 opacity-0 shadow-xl transition group-hover:scale-100 group-hover:opacity-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white">{{ $item['label'] }}</span>
                </a>
            @endforeach
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" data-logout class="group relative flex h-11 min-h-11 w-full items-center gap-3 rounded-xl border border-transparent px-3 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-red-50 hover:text-red-600 dark:text-slate-300 dark:hover:bg-red-950/30 dark:hover:text-red-300" aria-label="Log out" title="Log out">
                    <span class="sidebar-nav-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition-all duration-200 group-hover:text-red-600 dark:text-slate-400 dark:group-hover:text-red-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m8-9V3a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2h-6a2 2 0 0 1-2-2v-1"/></svg>
                    </span>
                    <span class="sidebar-label flex-1 text-left">Log out</span>
                </button>
            </form>
        </nav>

    </div>
</aside>
