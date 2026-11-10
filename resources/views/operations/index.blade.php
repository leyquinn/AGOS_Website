<x-layouts.app title="Operations">
    <div class="mb-6 flex items-center justify-between">
        <p class="hidden text-sm text-slate-400 sm:block">Monitor automatically processed reports, verified flood heatmaps, and Barangay Official tagging.</p>
        <a href="{{ route('operations') }}" class="ml-auto rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">
            ↻ &nbsp; Refresh
        </a>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-xs font-bold text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Active Reports (Cleaned)', 'value' => number_format($totalActiveReports ?? 142), 'color' => 'text-slate-900 dark:text-slate-100'],
            ['label' => 'Verified Flood Reports', 'value' => number_format($validatedEventsCount ?? 18), 'color' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Barangays Affected', 'value' => number_format($barangaysAffectedCount ?? 9), 'color' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Avg. Confidence (CS)', 'value' => number_format($avgCsOverall ?? 0.81, 2), 'color' => 'text-blue-700 dark:text-blue-400'],
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
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-bold text-white">Barangay flood heatmap (Verified Model Output)</h2>
                        <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[9px] font-bold text-emerald-300">
                            {{ number_format($validatedEventsCount ?? 0) }} Verified Reports · {{ $barangaysAffectedCount ?? 0 }} Barangays
                        </span>
                    </div>
                    <p data-map-status class="mt-1 text-[10px] text-slate-400">Loading Davao City barangay boundaries...</p>
                </div>
                <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-400">
                    <span class="sr-only">Filter by barangay</span>
                    <select data-barangay-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                        <option value="">All barangays</option>
                    </select>
                </label>
            </div>
            <div class="relative h-[28rem] bg-[#0b2852]">
                <div
                    data-flood-map
                    data-boundary-url="{{ asset('data/davao-barangays.geojson') }}"
                    @if (! empty($mapPoints) && count($mapPoints) > 0) data-heat-points='@json($mapPoints)' @endif
                    @if (! empty($barangayReportCounts)) data-barangay-counts='@json($barangayReportCounts)' @endif
                    class="h-full w-full"
                ></div>
                <div class="pointer-events-none absolute bottom-0 left-0 right-0 bg-[#071738]/85 px-5 py-3 text-xs text-slate-400">
                    Showing Verified Flood Reports (<code>VERIFIED_ACTIONABLE</code>) clipped to Davao City barangay boundaries
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white">Verified Flood Report Feed</h2>
                <span class="text-[10px] text-slate-400">Sort: Confidence ▼</span>
            </div>
            <div class="max-h-[28rem] space-y-3 overflow-y-auto pr-1">
                @if (! empty($actionablePosts) && $actionablePosts->isNotEmpty())
                    @foreach ($actionablePosts as $post)
                        @php
                            $cs = (float) $post->confidence_score;
                            $isOfficial = $post->discard_reason === 'verified_by_official';
                            $badgeColor = ($post->is_whitelisted_source || $isOfficial) ? 'bg-blue-500' : ($cs >= 0.75 ? 'bg-emerald-500' : ($cs >= 0.50 ? 'bg-amber-400' : 'bg-red-500'));
                            $badgeLabel = 'CS '.number_format($cs, 2).($isOfficial ? ' · official tagged' : ($post->is_whitelisted_source ? ' · verified' : ''));
                        @endphp
                        <article class="rounded-xl bg-[#0d2b5b] p-3">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-xs font-bold text-blue-300">Brgy. {{ $post->primary_barangay ?? 'Davao City' }}</h3>
                                <span class="{{ $badgeColor }} rounded px-2 py-1 text-[9px] font-bold text-white">{{ $badgeLabel }}</span>
                            </div>
                            <p class="mt-2 text-[10px] leading-relaxed text-slate-300">{{ \Illuminate\Support\Str::limit($post->cleaned_text ?: $post->full_text, 160) }}</p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[9px] text-slate-400">{{ $post->author }} · {{ $post->raw_timestamp ?: '' }}</span>
                                @if ($cs >= 0.75)
                                    <button type="button" data-broadcast-alert class="rounded-lg bg-blue-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-blue-500">Broadcast Alert</button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <section id="official-tagging-queue" class="mt-6 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
        <div class="flex flex-col gap-3 border-b border-blue-900/50 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-sm font-bold text-white">Barangay Official Tagging Queue — Flood Mentioned / No Urgency</h2>
                    <span class="rounded-full bg-amber-400 px-2.5 py-0.5 text-[9px] font-bold text-amber-950">
                        {{ number_format($noUrgencyTotalCount ?? 0) }} Awaiting Barangay Official Review
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-400">
                    Cleaned posts classified by the model as <code>FLOOD_MENTION_NO_URGENCY</code>. Barangay Officials can confirm the barangay location and tag a post as flood-related to promote it to <code>VERIFIED_ACTIONABLE</code> and plot it on the heatmap above.
                </p>
            </div>

            <form method="GET" action="{{ route('operations') }}#official-tagging-queue" class="flex flex-wrap items-center gap-2">
                <label for="review-barangay-select" class="text-[10px] font-semibold text-slate-400">Filter by barangay:</label>
                <select
                    id="review-barangay-select"
                    name="review_barangay"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-[10px] font-bold text-slate-200 outline-none focus:border-cyan-400"
                >
                    <option value="">All barangays ({{ number_format($noUrgencyTotalCount ?? 0) }})</option>
                    @foreach (($barangayList ?? []) as $brgyOption)
                        <option value="{{ $brgyOption }}" {{ ($reviewBarangay ?? '') === $brgyOption ? 'selected' : '' }}>
                            {{ $brgyOption }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse (($noUrgencyPosts ?? []) as $watchPost)
                <article class="flex flex-col justify-between rounded-xl border border-amber-500/30 bg-[#0d2b5b] p-4">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-blue-200">{{ $watchPost->author }}</span>
                                <span class="rounded-full bg-amber-400/20 px-2 py-0.5 text-[9px] font-bold text-amber-300">
                                    ⚠ NO URGENCY MENTIONED
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="rounded bg-blue-950/90 px-2 py-0.5 font-mono text-[9px] font-bold text-cyan-300">
                                    P_rel {{ number_format((float) $watchPost->p_rel, 2) }}
                                </span>
                                <span class="text-[10px] text-slate-400">{{ $watchPost->raw_timestamp ?: '' }}</span>
                            </div>
                        </div>

                        <p class="mt-2.5 text-xs leading-relaxed text-slate-200">{{ \Illuminate\Support\Str::limit($watchPost->cleaned_text ?: $watchPost->full_text, 280) }}</p>
                    </div>

                    <div class="mt-4 border-t border-blue-900/50 pt-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <form method="POST" action="{{ route('harvesting.posts.verify', $watchPost) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label class="text-[10px] font-bold text-slate-300">Barangay:</label>
                                <select
                                    name="barangay"
                                    class="rounded-lg border border-blue-800 bg-[#061836] px-2.5 py-1.5 text-[10px] font-bold text-cyan-200 outline-none focus:border-cyan-400"
                                >
                                    @php
                                        $defaultBrgy = auth()->user()?->barangay?->name ?: ($watchPost->primary_barangay ?: 'Talomo');
                                    @endphp
                                    @foreach (($barangayList ?? []) as $brgyOption)
                                        <option value="{{ $brgyOption }}" {{ $defaultBrgy === $brgyOption ? 'selected' : '' }}>
                                            Brgy. {{ $brgyOption }}
                                        </option>
                                    @endforeach
                                </select>
                                <button
                                    type="submit"
                                    class="rounded-lg bg-emerald-500 px-3 py-1.5 text-[10px] font-bold text-emerald-950 shadow transition hover:bg-emerald-400"
                                >
                                    ✓ Tag as Flood Related (Add to Heatmap)
                                </button>
                            </form>

                            <form method="POST" action="{{ route('harvesting.posts.dismiss', $watchPost) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-lg border border-rose-500/40 bg-rose-500/15 px-2.5 py-1.5 text-[10px] font-bold text-rose-300 transition hover:bg-rose-500/30"
                                >
                                    ✕ Dismiss
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-blue-800/60 p-6 text-center text-xs text-slate-400">
                    No pending <code>FLOOD_MENTION_NO_URGENCY</code> posts for this barangay filter.
                </div>
            @endforelse
        </div>

        @if (! empty($noUrgencyPosts) && $noUrgencyPosts->hasPages())
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-blue-900/50 pt-3 text-xs text-slate-400">
                <span>
                    Showing <strong class="text-slate-200">{{ $noUrgencyPosts->firstItem() }}</strong>–<strong class="text-slate-200">{{ $noUrgencyPosts->lastItem() }}</strong> of <strong class="text-slate-200">{{ number_format($noUrgencyPosts->total()) }}</strong> posts awaiting review
                </span>
                <div class="flex items-center gap-2">
                    @if (! $noUrgencyPosts->onFirstPage())
                        <a href="{{ $noUrgencyPosts->previousPageUrl() }}#official-tagging-queue" class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-1.5 text-[11px] font-semibold text-cyan-300 hover:bg-blue-900/60">« Previous</a>
                    @endif
                    <span class="text-[10px] text-slate-500">Page {{ $noUrgencyPosts->currentPage() }} of {{ $noUrgencyPosts->lastPage() }}</span>
                    @if ($noUrgencyPosts->hasMorePages())
                        <a href="{{ $noUrgencyPosts->nextPageUrl() }}#official-tagging-queue" class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-1.5 text-[11px] font-semibold text-cyan-300 hover:bg-blue-900/60">Next »</a>
                    @endif
                </div>
            </div>
        @endif
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