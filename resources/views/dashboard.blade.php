<x-layouts.app title="Dashboard">
    <div class="w-full">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm leading-relaxed text-slate-400">A real-time overview of flood intelligence activity across the AGOS portal.</p>
                <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Davao City • Last sync 10:17:03</p>
            </div>
            <a href="{{ route('operations') }}" class="rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">Open operations</a>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Portal overview metrics">
            @foreach ([
                ['label' => 'Active Reports', 'value' => '142', 'detail' => '+12 today', 'color' => 'text-slate-900 dark:text-slate-100', 'detailColor' => 'text-emerald-600 dark:text-emerald-400'],
                ['label' => 'Validated Events', 'value' => '18', 'detail' => '4 awaiting review', 'color' => 'text-emerald-600 dark:text-emerald-400', 'detailColor' => 'text-amber-600 dark:text-amber-400'],
                ['label' => 'Barangays Affected', 'value' => '9', 'detail' => '2 high priority', 'color' => 'text-rose-600 dark:text-rose-400', 'detailColor' => 'text-rose-600 dark:text-rose-400'],
                ['label' => 'Avg. Confidence', 'value' => '0.81', 'detail' => '+0.06 this week', 'color' => 'text-blue-700 dark:text-blue-400', 'detailColor' => 'text-blue-700 dark:text-blue-400'],
            ] as $stat)
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                    <p class="text-xs font-semibold text-slate-400">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-4xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-[10px] font-semibold {{ $stat['detailColor'] }}">{{ $stat['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-5" data-flood-dashboard>
            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Flood intelligence activity</h2>
                    <p class="mt-1 text-[10px] text-slate-400">Incoming reports and validated events for <span data-period-label>Last 7 Days</span></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-lg border border-blue-900/60 bg-[#031027] p-1" role="group" aria-label="Analytics time period">
                        @foreach ([['key' => '7d', 'label' => '7 Days'], ['key' => '4w', 'label' => '4 Weeks'], ['key' => '12m', 'label' => '12 Months'], ['key' => 'ytd', 'label' => 'YTD']] as $filter)
                            <button type="button" data-chart-filter="{{ $filter['key'] }}" aria-pressed="{{ $filter['key'] === '7d' ? 'true' : 'false' }}" class="rounded-md px-2.5 py-1.5 text-[10px] font-bold transition {{ $filter['key'] === '7d' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $filter['label'] }}</button>
                        @endforeach
                    </div>
                    <span class="group relative inline-flex cursor-help items-center gap-1 rounded-full border border-amber-500/40 bg-amber-500/10 px-2.5 py-1.5 text-[9px] font-bold text-amber-300" title="Live ML model sync will connect to this data layer.">
                        <span aria-hidden="true">●</span> Static Benchmark Mode
                        <span class="pointer-events-none absolute bottom-full right-0 mb-2 hidden w-52 rounded-lg bg-slate-900 p-2 text-[10px] font-normal leading-relaxed text-slate-200 shadow-xl group-hover:block">Live ML model sync will connect to this data layer when the classification and clustering services are ready.</span>
                    </span>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <div class="mb-3 flex items-center justify-between"><div><h3 class="text-xs font-bold text-white">Flood Incident Volume vs. Validated Alerts</h3><p class="mt-1 text-[10px] text-slate-400">Reports harvested and alerts confirmed by intelligence review</p></div><a href="{{ route('reports') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">View reports →</a></div>
                    <div class="h-64"><canvas data-chart="activity" aria-label="Flood incident volume line chart"></canvas></div>
                </article>
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h3 class="text-xs font-bold text-white">Confidence Score Distribution</h3><p class="mt-1 text-[10px] text-slate-400">Classification confidence across crowd signals</p>
                    <div class="mt-3 h-56"><canvas data-chart="confidence" aria-label="Confidence score distribution bar chart"></canvas></div>
                </article>
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h3 class="text-xs font-bold text-white">Top Affected Davao Barangays</h3><p class="mt-1 text-[10px] text-slate-400">Validated flood alerts by location</p>
                    <div class="mt-3 h-64"><canvas data-chart="barangays" aria-label="Top affected barangays horizontal bar chart"></canvas></div>
                </article>
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h3 class="text-xs font-bold text-white">Source Signal Breakdown</h3><p class="mt-1 text-[10px] text-slate-400">Origin of signals included in the benchmark</p>
                    <div class="mt-3 h-64"><canvas data-chart="sources" aria-label="Source signal breakdown doughnut chart"></canvas></div>
                </article>
            </div>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between"><h2 class="text-sm font-bold text-white">System health</h2><span class="rounded-full bg-emerald-500 px-2.5 py-1 text-[9px] font-bold text-emerald-950">Operational</span></div>
                <div class="mt-5 space-y-4">
                    @foreach ([['label' => 'Facebook', 'value' => 'Connected', 'class' => 'text-emerald-400'], ['label' => 'Twitter / X', 'value' => 'Connected', 'class' => 'text-emerald-400'], ['label' => 'Reddit', 'value' => 'Degraded', 'class' => 'text-red-400'], ['label' => 'Preprocessing queue', 'value' => '12 pending', 'class' => 'text-amber-300'], ['label' => 'ML analysis queue', 'value' => '47 pending', 'class' => 'text-amber-300']] as $health)
                        <div class="flex items-center justify-between border-b border-blue-900/40 pb-3 last:border-0 last:pb-0"><span class="text-xs text-slate-400">{{ $health['label'] }}</span><span class="text-xs font-bold {{ $health['class'] }}">{{ $health['value'] }}</span></div>
                    @endforeach
                </div>
                <a href="{{ route('harvesting') }}" class="mt-5 block text-center text-[10px] font-bold text-blue-300 hover:text-blue-200">Manage sources &amp; harvesting →</a>
            </article>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">Recent validated reports</h2>
                    <a href="{{ route('reports') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">View all →</a>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach ([['place' => 'Brgy. Poblacion', 'text' => 'Grabe ang baha diri sa Poblacion', 'score' => 'CS 0.91', 'time' => '3 min ago'], ['place' => 'Brgy. Matina', 'text' => 'Flooding sa may creek side, watch out', 'score' => 'CS 0.88', 'time' => '8 min ago'], ['place' => 'Brgy. Talomo', 'text' => 'Official advisory: road closure due to overflow', 'score' => 'CS 1.00', 'time' => '14 min ago']] as $report)
                        <div class="flex items-center gap-3 rounded-xl bg-[#0d2b5b] p-3"><span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400"></span><div class="min-w-0 flex-1"><p class="text-xs font-bold text-blue-300">{{ $report['place'] }}</p><p class="mt-1 truncate text-[10px] text-slate-300">{{ $report['text'] }}</p></div><div class="shrink-0 text-right"><span class="rounded bg-emerald-500 px-2 py-1 text-[9px] font-bold text-emerald-950">{{ $report['score'] }}</span><p class="mt-1 text-[9px] text-slate-500">{{ $report['time'] }}</p></div></div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">Model performance</h2>
                    <a href="{{ route('analytics') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">Open analytics →</a>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                    @foreach ([['label' => 'Accuracy', 'value' => '0.92'], ['label' => 'Precision', 'value' => '0.89'], ['label' => 'F1-score', 'value' => '0.81']] as $metric)
                        <div class="rounded-xl bg-[#0d2b5b] px-3 py-4"><p class="text-[10px] text-slate-400">{{ $metric['label'] }}</p><p class="mt-2 text-2xl font-black text-blue-300">{{ $metric['value'] }}</p></div>
                    @endforeach
                </div>
                <div class="mt-5 rounded-xl bg-[#0d2b5b] p-4"><div class="flex items-center justify-between text-xs"><span class="font-bold text-slate-300">Current model</span><span class="font-bold text-emerald-400">mtl-xlmr-v1.2</span></div><div class="mt-3 h-2 rounded-full bg-blue-950"><div class="h-2 w-[81%] rounded-full bg-blue-500"></div></div><p class="mt-2 text-[10px] text-slate-500">Integrated pipeline performance is trending upward.</p></div>
            </article>
        </section>
    </div>
</x-layouts.app>