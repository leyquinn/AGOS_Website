<x-layouts.app title="Operations">
    <div class="mb-6 flex items-center justify-between">
        <p class="hidden text-sm text-slate-400 sm:block">Monitor incoming reports and validate emerging flood events.</p>
        <button type="button" class="ml-auto rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">
            ↻ &nbsp; Refresh
        </button>
    </div>

    <section class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Active Reports', 'value' => '142', 'color' => 'text-white'],
            ['label' => 'Validated Events', 'value' => '18', 'color' => 'text-emerald-400'],
            ['label' => 'Barangays Affected', 'value' => '9', 'color' => 'text-red-400'],
            ['label' => 'Avg. Confidence (CS)', 'value' => '0.81', 'color' => 'text-blue-400'],
        ] as $stat)
            <div class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10 transition hover:-translate-y-0.5 hover:border-blue-700/70">
                <p class="text-xs font-semibold text-slate-400">{{ $stat['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-5 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
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
            <div class="flex items-center justify-between border-b border-blue-900/50 px-5 py-4">
                <h2 class="text-sm font-bold text-white">Barangay flood map</h2>
                <div class="flex gap-2 text-[10px] font-semibold">
                    <span class="rounded bg-blue-500 px-2 py-1 text-white">Markers</span>
                    <span class="rounded bg-blue-950 px-2 py-1 text-slate-400">Heatmap</span>
                </div>
            </div>
            <div class="relative h-[24rem] overflow-hidden bg-[#0b2852] bg-[linear-gradient(25deg,transparent_45%,rgba(147,197,253,.2)_46%,transparent_48%),linear-gradient(115deg,transparent_45%,rgba(147,197,253,.2)_46%,transparent_48%)] bg-[length:90px_70px]">
                <div class="absolute inset-0 flex flex-wrap content-center justify-center gap-4 p-12 opacity-90">
                    @foreach (range(1, 55) as $marker)
                        <span class="h-3 w-3 rounded-full {{ $marker % 5 === 0 ? 'bg-red-400' : ($marker % 3 === 0 ? 'bg-amber-300' : 'bg-cyan-400') }} shadow-lg shadow-cyan-300/30"></span>
                    @endforeach
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-[#071738]/80 px-5 py-3 text-xs text-slate-400">● High CS &nbsp; <span class="text-amber-400">●</span> Medium &nbsp; <span class="text-red-400">●</span> Low / outlier</div>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white">Validated Report Feed</h2>
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
                        @if ($loop->index < 2)
                            <div class="mt-2 flex gap-2"><button class="rounded bg-blue-600 px-3 py-1 text-[10px] font-bold text-white">Confirm</button><button class="rounded bg-blue-950 px-3 py-1 text-[10px] font-bold text-blue-200">False Alarm</button></div>
                        @elseif ($loop->index === 2)
                            <button class="mt-2 rounded bg-blue-600 px-3 py-1 text-[10px] font-bold text-white">Broadcast Alert</button>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>