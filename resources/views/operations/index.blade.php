<x-layouts.app title="Operations">
    <div class="mb-6 flex items-center justify-between">
        <p class="hidden text-sm text-slate-400 sm:block">Monitor automatically processed reports and emerging flood events.</p>
        <button type="button" class="ml-auto rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">
            ↻ &nbsp; Refresh
        </button>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Active Reports', 'value' => '142', 'color' => 'text-slate-900 dark:text-slate-100'],
            ['label' => 'Validated Events', 'value' => '18', 'color' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Barangays Affected', 'value' => '9', 'color' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Avg. Confidence (CS)', 'value' => '0.81', 'color' => 'text-blue-700 dark:text-blue-400'],
        ] as $stat)
            <div class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10 transition hover:-translate-y-0.5 hover:border-blue-700/70">
                <p class="text-xs font-semibold text-slate-400">{{ $stat['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="portal-card mt-5 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <p class="text-sm font-bold text-white">Weather Condition - Davao City</p>
                <p class="mt-2 text-3xl font-black text-white">☁ <span class="ml-2 text-lg font-medium text-slate-300">28.5°C</span></p>
            </div>
            <div class="grid grid-cols-2 gap-8 text-center text-xs text-slate-400">
                <span>Today's high / low<strong class="mt-1 block text-sm text-slate-200">32.5° / 26.2°</strong></span>
                <span>Rain chance (Today)<strong class="mt-1 block text-sm text-slate-200">20%</strong></span>
            </div>
            <div class="text-left md:text-right">
                <p class="text-xs text-slate-400">PAGASA Rainfall Signal</p>
                <span class="mt-2 inline-block rounded-lg bg-emerald-500 px-3 py-1 text-xs font-bold text-emerald-950">No warning</span>
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.4fr_0.8fr]">
        <div class="overflow-hidden rounded-2xl border border-blue-900/50 bg-[#071738]/80">
            <div class="flex flex-col gap-3 border-b border-blue-900/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Barangay flood heatmap</h2>
                    <p data-map-status class="mt-1 text-[10px] text-slate-400">Loading Davao City barangay boundaries...</p>
                </div>
                <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-400">
                    <span class="sr-only">Filter by barangay</span>
                    <select data-barangay-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                        <option value="">All barangays</option>
                    </select>
                </label>
            </div>
            <div class="relative h-[24rem] bg-[#0b2852]">
                <div data-flood-map data-boundary-url="{{ asset('data/davao-barangays.geojson') }}" class="h-full w-full"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 right-0 bg-[#071738]/85 px-5 py-3 text-xs text-slate-400">Heat intensity clipped to the active barangay boundary</div>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white">Processed Report Feed</h2>
                <span class="text-[10px] text-slate-400">Sort: Confidence ▼</span>
            </div>
            <div class="space-y-3">
                @foreach ([
                    ['name' => 'Brgy. Poblacion', 'text' => '"grabe baha sa poblacion, hanggang tuhod na"', 'score' => 'CS 0.91', 'color' => 'bg-emerald-500'],
                    ['name' => 'Brgy. Matina', 'text' => '"flooding sa may creek side, watch out"', 'score' => 'CS 0.68', 'color' => 'bg-amber-400'],
                    ['name' => 'Brgy. Talomo', 'text' => 'Official DRRMO advisory: road closure due to overflow', 'score' => 'CS 1.00 · verified', 'color' => 'bg-blue-500'],
                    ['name' => 'Brgy. Bunawan', 'text' => 'lone report, no corroborating cluster yet', 'score' => 'CS 0.34 · cold start', 'color' => 'bg-red-500'],
                ] as $report)
                    <article class="rounded-xl bg-[#0d2b5b] p-3">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-xs font-bold text-blue-300">{{ $report['name'] }}</h3>
                            <span class="{{ $report['color'] }} rounded px-2 py-1 text-[9px] font-bold text-white">{{ $report['score'] }}</span>
                        </div>
                        <p class="mt-2 text-[10px] leading-relaxed text-slate-300">{{ $report['text'] }}</p>
                        @if ($loop->index === 2)
                            <button type="button" data-broadcast-alert class="mt-2 rounded-lg bg-blue-600 px-3 py-2 text-[10px] font-bold text-white transition hover:bg-blue-500">Broadcast Alert</button>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    <div data-broadcast-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-5 backdrop-blur-sm">
        <form data-broadcast-form class="w-full max-w-md rounded-2xl border border-blue-900/50 bg-white p-5 shadow-2xl dark:bg-[#071738]">
            <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-900 dark:text-white">Broadcast alert</h2><button type="button" data-close-broadcast class="text-2xl text-slate-500">×</button></div>
            <label for="broadcast-message" class="mt-5 block text-xs font-bold text-slate-600 dark:text-slate-300">Message</label>
            <textarea id="broadcast-message" required class="mt-2 min-h-28 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500 dark:border-blue-900/60 dark:bg-slate-950/40 dark:text-white" placeholder="Describe the alert for affected residents"></textarea>
            <button type="submit" class="mt-4 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-500">Send notification</button>
        </form>
    </div>
</x-layouts.app>