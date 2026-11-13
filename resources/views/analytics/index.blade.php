<x-layouts.app title="Model Analytics">
    <div class="w-full" data-model-analytics data-analytics-windows='@json($analyticsWindows)'>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="max-w-xl text-sm leading-relaxed text-slate-400">
                    Live inference telemetry, multi-task scoring, strict-filter audit, and K-Means++ spatial consensus across Davao barangays.
                </p>
                <p class="mt-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    Last ML inference: <span data-hitl-field="lastAnalyzedFormatted">{{ $initial['hitlAudit']['lastAnalyzedFormatted'] }}</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border {{ $checkpoint['exists'] ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/40 bg-amber-500/10 text-amber-300' }} px-3 py-1.5 text-[9px] font-bold uppercase tracking-wide">
                    <span aria-hidden="true">●</span> {{ $checkpoint['exists'] ? 'Live Pipeline Telemetry' : 'Checkpoint Missing' }}
                </span>
                <select data-model-window class="rounded-xl border border-blue-900/60 bg-[#071738] px-3.5 py-2 text-[11px] font-bold text-slate-200 outline-none focus:ring-2 focus:ring-blue-500" aria-label="Select analysis window">
                    @foreach ($windowLabels as $key => $label)
                        <option value="{{ $key }}" @selected($key === $defaultWindow)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Model pipeline summary metrics">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <p class="text-xs font-semibold text-slate-400">Posts Analyzed by XLM-R</p>
                <p data-analytics-kpi="analyzedCount" class="mt-3 text-4xl font-black text-white">{{ number_format($initial['summary']['analyzedCount']) }}</p>
                <p data-analytics-sub="analyzedDetail" class="mt-2 text-[10px] font-semibold text-blue-300">
                    {{ $initial['summary']['analyzedPct'] }}% of {{ number_format($initial['summary']['totalInWindow']) }} posts ({{ number_format($initial['summary']['pendingCount']) }} pending)
                </p>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <p class="text-xs font-semibold text-slate-400">Verified Actionable</p>
                <p data-analytics-kpi="actionableCount" class="mt-3 text-4xl font-black text-emerald-400">{{ number_format($initial['summary']['actionableCount']) }}</p>
                <p data-analytics-sub="actionableDetail" class="mt-2 text-[10px] font-semibold text-emerald-300">
                    {{ $initial['summary']['actionableRate'] }}% yield • Avg CS {{ number_format((float) $initial['summary']['avgActionableCs'], 2) }}
                </p>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <p class="text-xs font-semibold text-slate-400">Flood Watch (Early Warning)</p>
                <p data-analytics-kpi="watchCount" class="mt-3 text-4xl font-black text-amber-400">{{ number_format($initial['summary']['watchCount']) }}</p>
                <p data-analytics-sub="watchDetail" class="mt-2 text-[10px] font-semibold text-amber-300">
                    {{ $initial['summary']['watchRate'] }}% of analyzed • {{ number_format($initial['summary']['discardedTotal']) }} discarded
                </p>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <p class="text-xs font-semibold text-slate-400">Mean Spatial Consensus (D_clusters)</p>
                <p data-analytics-kpi="meanDClusters" class="mt-3 text-4xl font-black text-blue-300">{{ number_format((float) $initial['summary']['meanDClusters'], 2) }}</p>
                <p data-analytics-sub="consensusDetail" class="mt-2 text-[10px] font-semibold text-slate-400">
                    {{ number_format($initial['summary']['uniqueClusters']) }} K-Means++ clusters • Flood Avg P_rel {{ number_format((float) $initial['summary']['meanFloodPRel'], 2) }}
                </p>
            </article>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-white">Pipeline Outcome &amp; Scoring Matrix</h2>
                        <p class="mt-0.5 text-[10px] text-slate-400">Real classification breakdown with mean P_rel, D_clusters, and final CS</p>
                    </div>
                    <span class="rounded-full bg-[#0d2b5b] px-2.5 py-1 text-[9px] font-bold text-blue-300">CS = 0.35·P_rel + 0.65·D_clusters</span>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[26rem] text-left text-[11px]">
                        <thead class="border-b border-blue-900/60 text-[10px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="pb-2.5 font-semibold">Classification Tier</th>
                                <th class="pb-2.5 text-right font-semibold">Count</th>
                                <th class="pb-2.5 text-right font-semibold">Share</th>
                                <th class="pb-2.5 text-right font-semibold">Avg P_rel</th>
                                <th class="pb-2.5 text-right font-semibold">Avg D_cl</th>
                                <th class="pb-2.5 text-right font-semibold">Avg CS</th>
                            </tr>
                        </thead>
                        <tbody data-outcome-matrix class="divide-y divide-blue-900/40 text-slate-200">
                            @foreach ($initial['outcomeMatrix'] as $row)
                                @php
                                    $dotClass = match ($row['tone']) {
                                        'emerald' => 'bg-emerald-400',
                                        'amber' => 'bg-amber-400',
                                        'rose' => 'bg-rose-400',
                                        default => 'bg-slate-400',
                                    };
                                @endphp
                                <tr>
                                    <td class="py-2.5 font-semibold">
                                        <span class="mr-2 inline-block h-2 w-2 rounded-full {{ $dotClass }}"></span>{{ $row['label'] }}
                                    </td>
                                    <td class="py-2.5 text-right font-bold">{{ number_format($row['count']) }}</td>
                                    <td class="py-2.5 text-right text-slate-400">{{ number_format($row['sharePct'], 1) }}%</td>
                                    <td class="py-2.5 text-right font-mono">{{ number_format($row['avgPRel'], 2) }}</td>
                                    <td class="py-2.5 text-right font-mono">{{ number_format($row['avgDClusters'], 2) }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold text-blue-300">{{ number_format($row['avgCs'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-[#0d2b5b] px-3.5 py-2.5 text-[10px] text-slate-300">
                    <span>Whitelisted Official Sources: <strong data-hitl-field="whitelistedCount" class="text-white">{{ number_format($initial['hitlAudit']['whitelistedCount']) }}</strong></span>
                    <span>HITL Verified: <strong data-hitl-field="hitlVerifiedCount" class="text-emerald-300">{{ number_format($initial['hitlAudit']['hitlVerifiedCount']) }}</strong></span>
                    <span>HITL Dismissed: <strong data-hitl-field="hitlDismissedCount" class="text-rose-300">{{ number_format($initial['hitlAudit']['hitlDismissedCount']) }}</strong></span>
                </div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-white">Gate &amp; Strict Filter Decision Audit</h2>
                        <p class="mt-0.5 text-[10px] text-slate-400">Exact attribution of Watch gating, strict domain filtering, and spatial rejection</p>
                    </div>
                </div>
                <div data-filter-reasons class="mt-4 max-h-72 space-y-2.5 overflow-y-auto pr-1">
                    @forelse ($initial['filterReasons'] as $reason)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-[#0d2b5b] px-3.5 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-200">{{ $reason['label'] }}</p>
                                <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-blue-300">{{ $reason['gate'] }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-black text-white">{{ number_format($reason['count']) }}</p>
                                <p class="text-[9px] text-slate-400">{{ number_format($reason['sharePct'], 1) }}%</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl bg-[#0d2b5b] p-4 text-center text-xs text-slate-400">
                            No gate or discard attributions recorded in this window.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="mb-3">
                    <h2 class="text-sm font-bold text-white">Multi-Task Score Profile by Outcome Tier</h2>
                    <p class="mt-0.5 text-[10px] text-slate-400">Comparing Task Head A (P_rel), K-Means++ (D_clusters), and fused Confidence (CS)</p>
                </div>
                <div class="h-60"><canvas data-model-chart="scoring" aria-label="Multi-task score profile chart"></canvas></div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="mb-3">
                    <h2 class="text-sm font-bold text-white">Relevance (P_rel) vs. Final Confidence (CS) Histogram</h2>
                    <p class="mt-0.5 text-[10px] text-slate-400">Distribution shift after spatial consensus weighting across analyzed posts</p>
                </div>
                <div class="h-60"><canvas data-model-chart="distribution" aria-label="Relevance versus confidence histogram"></canvas></div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="mb-3">
                    <h2 class="text-sm font-bold text-white">Classification Yield Over Time</h2>
                    <p class="mt-0.5 text-[10px] text-slate-400">Actionable, Flood Watch, and Discarded volume across time buckets</p>
                </div>
                <div class="h-64"><canvas data-model-chart="yield" aria-label="Classification yield over time chart"></canvas></div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="mb-3">
                    <h2 class="text-sm font-bold text-white">River Basin Signal Distribution</h2>
                    <p class="mt-0.5 text-[10px] text-slate-400">Analyzed flood intelligence mapped to Davao City hydrological catchments</p>
                </div>
                <div class="h-64"><canvas data-model-chart="basins" aria-label="River basin distribution chart"></canvas></div>
            </article>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-white">Per-Barangay Model Performance (Top 10)</h2>
                        <p class="mt-0.5 text-[10px] text-slate-400">Live NER + Gazetteer localization volume and average pipeline scores per barangay</p>
                    </div>
                    <a href="{{ route('operations') }}" class="text-[10px] font-bold text-blue-300 hover:text-blue-200">Open Operations →</a>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[34rem] text-left text-[11px]">
                        <thead class="border-b border-blue-900/60 text-[10px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="pb-2.5 font-semibold">Barangay</th>
                                <th class="pb-2.5 text-right font-semibold">Total</th>
                                <th class="pb-2.5 text-right font-semibold">Actionable</th>
                                <th class="pb-2.5 text-right font-semibold">Watch</th>
                                <th class="pb-2.5 text-right font-semibold">Yield</th>
                                <th class="pb-2.5 text-right font-semibold">Avg P_rel</th>
                                <th class="pb-2.5 text-right font-semibold">Avg D_cl</th>
                                <th class="pb-2.5 text-right font-semibold">Avg CS</th>
                            </tr>
                        </thead>
                        <tbody data-barangay-table class="divide-y divide-blue-900/40 text-slate-200">
                            @forelse ($initial['barangays'] as $row)
                                <tr>
                                    <td class="py-2 font-bold text-blue-300">{{ $row['barangay'] }}</td>
                                    <td class="py-2 text-right font-bold">{{ number_format($row['total']) }}</td>
                                    <td class="py-2 text-right font-semibold text-emerald-400">{{ number_format($row['actionable']) }}</td>
                                    <td class="py-2 text-right text-amber-300">{{ number_format($row['watch']) }}</td>
                                    <td class="py-2 text-right text-slate-400">{{ number_format($row['actionableRate'], 1) }}%</td>
                                    <td class="py-2 text-right font-mono">{{ number_format($row['avgPRel'], 2) }}</td>
                                    <td class="py-2 text-right font-mono">{{ number_format($row['avgDClusters'], 2) }}</td>
                                    <td class="py-2 text-right font-mono font-bold text-white">{{ number_format($row['avgCs'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-xs text-slate-400">No localized barangay signals in this window.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-white">Checkpoint &amp; Architecture</h2>
                    <span class="rounded-full {{ $checkpoint['exists'] ? 'bg-emerald-500 text-emerald-950' : 'bg-amber-500 text-amber-950' }} px-2.5 py-0.5 text-[9px] font-bold">
                        {{ $checkpoint['exists'] ? 'Ready' : 'Missing' }}
                    </span>
                </div>
                <div class="mt-4 space-y-3 text-xs">
                    @foreach ([
                        ['label' => 'Checkpoint file', 'value' => $checkpoint['filename'] . ($checkpoint['exists'] ? ' (' . $checkpoint['sizeGb'] . ' GB)' : '')],
                        ['label' => 'Base encoder', 'value' => $checkpoint['encoder']],
                        ['label' => 'NER head schema', 'value' => $checkpoint['nerSchema']],
                        ['label' => 'Training checkpoint', 'value' => 'Epoch ' . $checkpoint['bestEpoch'] . ' • Val Loss ' . number_format($checkpoint['bestValLoss'], 4)],
                        ['label' => 'Loss weights', 'value' => $checkpoint['lossWeights']],
                        ['label' => 'Relevance threshold', 'value' => 'P_rel ≥ ' . number_format($checkpoint['threshold'], 2)],
                        ['label' => 'Consensus formula', 'value' => $checkpoint['csFormula']],
                        ['label' => 'Checkpoint updated', 'value' => $checkpoint['updatedAt']],
                    ] as $spec)
                        <div class="flex items-center justify-between gap-3 border-b border-blue-900/40 pb-2.5 last:border-0 last:pb-0">
                            <span class="text-slate-400">{{ $spec['label'] }}</span>
                            <span class="text-right font-semibold text-slate-200">{{ $spec['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>
    </div>
</x-layouts.app>