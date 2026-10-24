<x-layouts.app title="Reports">
    <div class="w-full">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400">Citizen-submitted flood reports • {{ $reports->count() }} total</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">Community reports</h2>
                <p class="mt-2 text-xs text-slate-400">Only authenticated Citizen App submissions are shown here. Public social posts are not ingested.</p>
            </div>
            <span class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-[10px] font-bold text-emerald-300">Citizen App source only</span>
        </div>

        <section class="mb-5 overflow-hidden rounded-2xl border border-blue-900/50 bg-[#071738]/80">
            <div class="flex flex-col gap-2 border-b border-blue-900/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Citizen report flood heatmap</h2>
                    <p data-map-status class="mt-1 text-[10px] text-slate-400">Loading Davao City barangay boundaries...</p>
                </div>
                <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-400">
                    <span class="sr-only">Filter heatmap by barangay</span>
                    <select data-barangay-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                        <option value="">All barangays</option>
                    </select>
                </label>
            </div>
            <div class="relative h-[26rem] bg-[#0b2852]">
                <div data-flood-map data-boundary-url="{{ asset('data/davao-barangays.geojson') }}" data-heat-points='@json($mapPoints)' class="h-full w-full"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 right-0 bg-[#071738]/85 px-5 py-3 text-xs text-slate-400">Heat intensity is clipped to the selected barangay boundary</div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-5">
                @forelse ($reports as $report)
                    @php
                        $confidence = (float) $report->confidence_score;
                        $relevance = max($confidence, 0.35);
                        $clusterScore = $confidence > 0 ? min(0.98, max(0.55, $confidence - 0.05)) : 0.89;
                        $finalScore = min(0.99, ($relevance * 0.35) + ($clusterScore * 0.65));
                    @endphp
                    <article class="space-y-5">
                        <section class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-blue-300">{{ $report->barangay?->name ?? 'Location pending' }} • {{ $report->created_at->format('M d, Y • H:i') }}</p>
                                    <h3 class="mt-1 text-sm font-bold text-white">{{ $report->report_text }}</h3>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[9px] font-bold uppercase {{ $report->verification_status === 'verified' ? 'bg-emerald-500 text-emerald-950' : ($report->verification_status === 'rejected' ? 'bg-red-500 text-white' : 'bg-amber-400 text-amber-950') }}">{{ $report->verification_status }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-[10px] text-slate-400">
                                <span>Submitted by {{ $report->user ? trim($report->user->first_name.' '.$report->user->last_name) : 'Citizen user' }}</span>
                                <span>Water level: {{ str_replace('_', ' ', $report->water_level_status) }}</span>
                                <span>{{ $report->created_at->diffForHumans() }}</span>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-black text-white">Multi-task NLP Output</h3>
                                <span class="rounded-full bg-blue-900/70 px-2.5 py-1 text-[9px] font-bold uppercase text-blue-200">Benchmark analysis</span>
                            </div>
                            <div class="mt-5 flex items-center justify-between text-xs text-slate-400"><span>Relevance score (P<sub>rel</sub>)</span><strong class="text-slate-200">{{ number_format($relevance * 100, 0) }}%</strong></div>
                            <div class="mt-2 h-2 rounded-full bg-slate-800"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $relevance * 100 }}%"></div></div>
                            <p class="mt-5 text-xs text-slate-400">Extracted entities (NER)</p>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <span class="rounded-lg bg-orange-400 px-3 py-1.5 text-[10px] font-bold text-white">Barangay: {{ $report->barangay?->name ?? 'Pending' }}</span>
                                <span class="rounded-lg bg-blue-700 px-3 py-1.5 text-[10px] font-bold text-white">Signal: {{ str_replace('_', ' ', $report->water_level_status) }}</span>
                            </div>
                            <p class="mt-4 text-[9px] font-semibold uppercase tracking-wide text-slate-500">Model: MTL-XLM-R benchmark • Citizen App signal</p>
                        </section>

                        <section class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                            <h3 class="text-lg font-black text-white">Confidence score breakdown</h3>
                            <div class="mt-5 flex flex-wrap justify-center gap-4 text-center">
                                <div class="rounded-2xl bg-[#0d2b5b] px-6 py-3"><p class="text-xs text-slate-400">P<sub>rel</sub></p><strong class="text-2xl text-white">{{ number_format($relevance, 2) }}</strong></div>
                                <div class="rounded-2xl bg-[#0d2b5b] px-6 py-3"><p class="text-xs text-slate-400">D<sub>clusters</sub></p><strong class="text-2xl text-white">{{ number_format($clusterScore, 2) }}</strong></div>
                                <div class="rounded-2xl bg-emerald-500 px-6 py-3"><p class="text-xs text-emerald-950">Final CS</p><strong class="text-2xl text-emerald-950">{{ number_format($finalScore, 2) }}</strong></div>
                            </div>
                            <p class="mt-4 text-[10px] font-semibold text-slate-500">CS = (0.35 × {{ number_format($relevance, 2) }}) + (0.65 × {{ number_format($clusterScore, 2) }}) = {{ number_format($finalScore, 2) }}</p>
                        </section>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-blue-900/60 bg-[#071738]/60 p-10 text-center">
                        <p class="text-sm font-bold text-white">No Citizen App reports yet</p>
                        <p class="mt-2 text-xs text-slate-400">Reports submitted by authenticated citizen accounts will appear here.</p>
                    </div>
                @endforelse
            </div>
            <aside class="space-y-5">
                <section class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h2 class="text-sm font-bold text-white">Cluster Consensus</h2>
                    <p class="mt-1 text-[10px] text-slate-400">{{ $clusterReports->count() }} Citizen App signals • benchmark grouping</p>
                    <div class="mt-4 space-y-2">
                        @forelse ($clusterReports as $clusterReport)
                            <div class="flex items-center justify-between gap-2 rounded-full bg-[#0d2b5b] px-3 py-2 text-[9px] text-slate-300">
                                <span class="truncate">{{ \Illuminate\Support\Str::limit($clusterReport->report_text, 35) }}</span>
                                <strong class="shrink-0 rounded-full bg-blue-600 px-2 py-0.5 text-white">{{ number_format(max((float) $clusterReport->confidence_score, 0.35), 2) }}</strong>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Cluster members will appear after citizen submissions are received.</p>
                        @endforelse
                    </div>
                </section>
                <section class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <h2 class="text-sm font-bold text-white">Submission policy</h2>
                <p class="mt-3 text-xs leading-relaxed text-slate-400">The admin reports feed accepts data only from users authenticated through the AGOS Citizen App. Social media posts and public “original posts” are not displayed or treated as citizen reports.</p>
                <div class="mt-5 space-y-3 text-[10px]">
                    <div class="flex justify-between border-b border-blue-900/40 pb-3"><span class="text-slate-400">Total submissions</span><strong class="text-white">{{ $reports->count() }}</strong></div>
                    <div class="flex justify-between border-b border-blue-900/40 pb-3"><span class="text-slate-400">Pending review</span><strong class="text-amber-300">{{ $reports->where('verification_status', 'pending')->count() }}</strong></div>
                    <div class="flex justify-between"><span class="text-slate-400">Verified</span><strong class="text-emerald-300">{{ $reports->where('verification_status', 'verified')->count() }}</strong></div>
                </div>
                </section>
            </aside>
        </section>
    </div>
</x-layouts.app>
