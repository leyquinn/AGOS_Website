<x-layouts.app title="Dashboard">
    <div class="w-full">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm leading-relaxed text-slate-400">A real-time overview of flood intelligence activity across the AGOS portal.</p>
                <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Davao City • Last sync {{ $lastSyncFormatted }}</p>
            </div>
            <a href="{{ route('operations') }}" class="rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">Open operations</a>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Portal overview metrics">
            @foreach ([
                ['label' => 'Active Reports', 'value' => number_format($activeReportsCount), 'detail' => '+' . number_format($recent7dReportsCount) . ' last 7 days', 'color' => 'text-slate-900 dark:text-slate-100', 'detailColor' => 'text-emerald-600 dark:text-emerald-400'],
                ['label' => 'Validated Events', 'value' => number_format($validatedEventsCount), 'detail' => number_format($awaitingReviewCount) . ' awaiting review', 'color' => 'text-emerald-600 dark:text-emerald-400', 'detailColor' => 'text-amber-600 dark:text-amber-400'],
                ['label' => 'Barangays Affected', 'value' => number_format($barangaysAffectedCount), 'detail' => number_format($highPriorityBarangays) . ' high priority', 'color' => 'text-rose-600 dark:text-rose-400', 'detailColor' => 'text-rose-600 dark:text-rose-400'],
                ['label' => 'Avg. Confidence', 'value' => number_format((float) $avgConfidence, 2), 'detail' => number_format($highConfidenceCount) . ' high confidence (CS ≥ 0.85)', 'color' => 'text-blue-700 dark:text-blue-400', 'detailColor' => 'text-blue-700 dark:text-blue-400'],
            ] as $stat)
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                    <p class="text-xs font-semibold text-slate-400">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-4xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-[10px] font-semibold {{ $stat['detailColor'] }}">{{ $stat['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-5" data-flood-dashboard data-chart-datasets='@json($chartDatasets)'>
            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Flood intelligence activity</h2>
                    <p class="mt-1 text-[10px] text-slate-400">Incoming reports and validated events for <span data-period-label>Last 7 Days</span></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-lg border border-blue-900/60 bg-[#031027] p-1" role="group" aria-label="Analytics time period">
                        @foreach ([['key' => '7d', 'label' => '7 Days'], ['key' => '4w', 'label' => '4 Weeks'], ['key' => '12m', 'label' => '12 Months'], ['key' => 'ytd', 'label' => 'All Years']] as $filter)
                            <button type="button" data-chart-filter="{{ $filter['key'] }}" aria-pressed="{{ $filter['key'] === '7d' ? 'true' : 'false' }}" class="rounded-md px-2.5 py-1.5 text-[10px] font-bold transition {{ $filter['key'] === '7d' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $filter['label'] }}</button>
                        @endforeach
                    </div>
                    <span class="group relative inline-flex cursor-help items-center gap-1 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2.5 py-1.5 text-[9px] font-bold text-emerald-300" title="Connected directly to MySQL flood intelligence tables.">
                        <span aria-hidden="true">●</span> Live Database Sync
                        <span class="pointer-events-none absolute bottom-full right-0 mb-2 hidden w-56 rounded-lg bg-slate-900 p-2 text-[10px] font-normal leading-relaxed text-slate-200 shadow-xl group-hover:block">Metrics and charts are queried live from Social Posts, Citizen Reports, Harvest Runs, and Broadcast Alerts in MySQL.</span>
                    </span>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <div class="mb-3 flex items-center justify-between"><div><h3 class="text-xs font-bold text-white">Flood Incident Volume vs. Validated Alerts</h3><p class="mt-1 text-[10px] text-slate-400">Reports harvested and alerts confirmed by intelligence review</p></div><a href="{{ route('reports') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">View reports →</a></div>
                    <div class="h-64"><canvas data-chart="activity" aria-label="Flood incident volume line chart"></canvas></div>
                </article>
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h3 class="text-xs font-bold text-white">Confidence Score Distribution</h3><p class="mt-1 text-[10px] text-slate-400">Classification confidence across analyzed signals</p>
                    <div class="mt-3 h-56"><canvas data-chart="confidence" aria-label="Confidence score distribution bar chart"></canvas></div>
                </article>
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h3 class="text-xs font-bold text-white">Top Affected Davao Barangays</h3><p class="mt-1 text-[10px] text-slate-400">Actionable flood alerts by location</p>
                    <div class="mt-3 h-64"><canvas data-chart="barangays" aria-label="Top affected barangays horizontal bar chart"></canvas></div>
                </article>
                <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                    <h3 class="text-xs font-bold text-white">Source Signal Breakdown</h3><p class="mt-1 text-[10px] text-slate-400">Origin of signals included in the selected window</p>
                    <div class="mt-3 h-64"><canvas data-chart="sources" aria-label="Source signal breakdown doughnut chart"></canvas></div>
                </article>
            </div>
        </section>

        @php
            $analyzedPct = $totalPosts > 0 ? min(100, round(($analyzedPostsCount / $totalPosts) * 100, 1)) : 0;
        @endphp

        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">System health</h2>
                    <span class="rounded-full {{ $checkpointReady ? 'bg-emerald-500 text-emerald-950' : 'bg-amber-500 text-amber-950' }} px-2.5 py-1 text-[9px] font-bold">
                        {{ $checkpointReady ? 'Operational' : 'Checkpoint Missing' }}
                    </span>
                </div>
                <div class="mt-5 space-y-4">
                    @foreach ([
                        ['label' => 'Verified Facebook sources', 'value' => $verifiedSourcesCount > 0 ? $verifiedSourcesCount . ' active' : 'No sources', 'class' => $verifiedSourcesCount > 0 ? 'text-emerald-400' : 'text-amber-300'],
                        ['label' => 'Preprocessing queue', 'value' => number_format($pendingCleanCount) . ' pending', 'class' => $pendingCleanCount > 0 ? 'text-amber-300' : 'text-emerald-400'],
                        ['label' => 'ML analysis queue', 'value' => number_format($pendingModelCount) . ' pending', 'class' => $pendingModelCount > 0 ? 'text-amber-300' : 'text-emerald-400'],
                        ['label' => 'Active citizen broadcasts', 'value' => number_format($activeBroadcastsCount) . ' active', 'class' => $activeBroadcastsCount > 0 ? 'text-blue-300' : 'text-slate-400'],
                    ] as $health)
                        <div class="flex items-center justify-between border-b border-blue-900/40 pb-3 last:border-0 last:pb-0"><span class="text-xs text-slate-400">{{ $health['label'] }}</span><span class="text-xs font-bold {{ $health['class'] }}">{{ $health['value'] }}</span></div>
                    @endforeach
                </div>
                <a href="{{ route('harvesting') }}" class="mt-5 block text-center text-[10px] font-bold text-blue-300 hover:text-blue-200">Manage sources &amp; harvesting →</a>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">Model pipeline status</h2>
                    <a href="{{ route('analytics') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">Open analytics →</a>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                    @foreach ([
                        ['label' => 'Processed Posts', 'value' => number_format($analyzedPostsCount)],
                        ['label' => 'Avg. Relevance (P_rel)', 'value' => number_format((float) $avgPRel, 2)],
                        ['label' => 'Avg. Confidence (CS)', 'value' => number_format((float) $avgConfidence, 2)],
                    ] as $metric)
                        <div class="rounded-xl bg-[#0d2b5b] px-3 py-4"><p class="text-[10px] text-slate-400">{{ $metric['label'] }}</p><p class="mt-2 text-2xl font-black text-blue-300">{{ $metric['value'] }}</p></div>
                    @endforeach
                </div>
                <div class="mt-5 rounded-xl bg-[#0d2b5b] p-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-300">Active model</span>
                        <span class="font-bold {{ $checkpointReady ? 'text-emerald-400' : 'text-amber-300' }}">best_model.pt · XLM-R + K-Means++</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-blue-950">
                        <div class="h-2 rounded-full bg-blue-500 transition-all" style="width: {{ $analyzedPct }}%"></div>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">{{ number_format($analyzedPostsCount) }} of {{ number_format($totalPosts) }} posts analyzed ({{ $analyzedPct }}%) • Latest harvest: {{ ucfirst($latestHarvestStatus ?? 'idle') }}</p>
                </div>
            </article>
        </section>

        <section class="mt-5">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">Recent validated reports</h2>
                    <a href="{{ route('reports') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">View all →</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($recentValidatedReports as $report)
                        @php
                            $placeLabel = $report->primary_barangay ? 'Brgy. ' . $report->primary_barangay : 'Davao City';
                            $snippet = \Illuminate\Support\Str::limit($report->cleaned_text ?: ($report->full_text ?? 'Validated actionable flood report.'), 140);
                            $timestamp = $report->scraped_at ?? $report->created_at;
                        @endphp
                        <div class="flex items-center gap-3 rounded-xl bg-[#0d2b5b] p-3">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-blue-300">{{ $placeLabel }}</p>
                                <p class="mt-1 truncate text-[10px] text-slate-300">{{ $snippet }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="rounded bg-emerald-500 px-2 py-1 text-[9px] font-bold text-emerald-950">CS {{ number_format((float) $report->confidence_score, 2) }}</span>
                                <p class="mt-1 text-[9px] text-slate-400">{{ $timestamp ? $timestamp->diffForHumans() : 'Recent' }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl bg-[#0d2b5b] p-4 text-center text-xs text-slate-400">
                            No validated actionable reports recorded yet.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-layouts.app>