<x-layouts.app title="Dashboard">
    <div class="w-full">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm leading-relaxed text-slate-400">A real-time overview of flood intelligence activity across the AGOS portal.</p>
                <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Davao City • Last sync 10:17:03</p>
            </div>
            <a href="{{ route('operations') }}" class="rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">Open operations</a>
        </div>

        <section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Portal overview metrics">
            @foreach ([
                ['label' => 'Active Reports', 'value' => '142', 'detail' => '+12 today', 'color' => 'text-white', 'detailColor' => 'text-emerald-400'],
                ['label' => 'Validated Events', 'value' => '18', 'detail' => '4 awaiting review', 'color' => 'text-emerald-400', 'detailColor' => 'text-amber-300'],
                ['label' => 'Barangays Affected', 'value' => '9', 'detail' => '2 high priority', 'color' => 'text-red-400', 'detailColor' => 'text-red-300'],
                ['label' => 'Avg. Confidence', 'value' => '0.81', 'detail' => '+0.06 this week', 'color' => 'text-blue-400', 'detailColor' => 'text-blue-300'],
            ] as $stat)
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                    <p class="text-xs font-semibold text-slate-400">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-4xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-[10px] font-semibold {{ $stat['detailColor'] }}">{{ $stat['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
            <article class="overflow-hidden rounded-2xl border border-blue-900/50 bg-[#071738]/80">
                <div class="flex items-center justify-between border-b border-blue-900/50 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-bold text-white">Flood intelligence activity</h2>
                        <p class="mt-1 text-[10px] text-slate-400">Incoming reports and validated events over the last 7 days</p>
                    </div>
                    <a href="{{ route('reports') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">View reports →</a>
                </div>
                <div class="relative h-64 px-5 pb-8 pt-6">
                    <div class="absolute inset-x-5 top-6 bottom-9 flex items-end justify-around gap-2 border-b border-blue-900/60 bg-[linear-gradient(to_top,rgba(59,130,246,.12)_1px,transparent_1px)] bg-[length:100%_25%]">
                        @foreach ([['Mon', 38, 18], ['Tue', 54, 26], ['Wed', 46, 22], ['Thu', 72, 34], ['Fri', 62, 29], ['Sat', 88, 45], ['Sun', 76, 39]] as $day)
                            <div class="flex h-full flex-1 items-end justify-center gap-1">
                                <span class="w-3 rounded-t bg-blue-400/70 sm:w-5" style="height: {{ $day[1] }}%"></span>
                                <span class="w-3 rounded-t bg-emerald-400 sm:w-5" style="height: {{ $day[2] }}%"></span>
                            </div>
                        @endforeach
                    </div>
                    <div class="absolute inset-x-5 bottom-2 flex justify-around text-[9px] text-slate-500"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
                </div>
                <div class="flex gap-5 border-t border-blue-900/50 px-5 py-3 text-[10px] text-slate-400"><span><i class="mr-1 inline-block h-2 w-2 bg-blue-400"></i>Reports harvested</span><span><i class="mr-1 inline-block h-2 w-2 bg-emerald-400"></i>Validated events</span></div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">System health</h2>
                    <span class="rounded-full bg-emerald-500 px-2.5 py-1 text-[9px] font-bold text-emerald-950">Operational</span>
                </div>
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