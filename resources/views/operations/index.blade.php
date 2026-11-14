<x-layouts.app title="Operations">
    <div class="operations-page">
    <div class="mb-6 flex items-center justify-between">
        <p class="hidden text-sm text-slate-400 sm:block">Monitor automatically processed reports, verified flood heatmaps, and Barangay Official tagging.</p>
        <button type="button" data-weather-refresh class="ml-auto rounded-xl border border-blue-700/60 bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">
            <svg aria-hidden="true" class="mr-1 inline-block h-4 w-4 align-[-0.2em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 11a8 8 0 0 0-14.9-3M4 5v4h4M4 13a8 8 0 0 0 14.9 3M20 19v-4h-4"/></svg>
            Refresh
        </button>
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
                <p class="mt-3 tabular-nums text-4xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section data-weather-card class="portal-card mt-5 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 md:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-[13rem]">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Current conditions</p>
                <h2 class="mt-1 text-base font-black text-white">Davao City</h2>
                <div class="mt-3 flex items-center gap-3">
                    <img data-weather-icon src="" alt="" class="h-12 w-12 object-contain" hidden>
                    <div>
                        <p data-weather-temperature class="text-3xl font-black text-white">Loading…</p>
                        <p data-weather-condition class="mt-1 text-xs font-medium text-slate-400">Loading live weather…</p>
                    </div>
                </div>
                <p data-weather-updated class="mt-3 text-[10px] text-slate-500">Waiting for WeatherAPI…</p>
            </div>
            <div class="grid grid-cols-2 gap-x-10 gap-y-4 sm:grid-cols-3 lg:flex lg:items-center lg:gap-10">
                <div><p class="text-xs text-slate-400">Today's high / low</p><strong data-weather-range class="mt-1 block text-base text-slate-200">—</strong></div>
                <div><p class="text-xs text-slate-400">Rain chance</p><strong data-weather-rain class="mt-1 block text-base text-slate-200">—</strong></div>
                <div class="sm:col-span-1"><p class="text-xs text-slate-400">Rainfall status</p><span data-weather-signal class="mt-2 inline-flex rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-emerald-950">Loading…</span></div>
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.4fr_0.8fr]">
        <div class="overflow-hidden rounded-2xl border border-blue-900/50 bg-[#071738]/80">
            <div class="flex flex-col gap-3 border-b border-blue-900/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-bold text-white">Barangay flood heatmap (Verified Model Output)</h2>
                        <span data-heatmap-badge class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[9px] font-bold text-emerald-300">
                            {{ number_format($validatedEventsCount ?? 0) }} Verified Reports · {{ $barangaysAffectedCount ?? 0 }} Barangays
                        </span>
                    </div>
                    <p data-map-status class="mt-1 text-[10px] text-slate-400">Loading Davao City barangay boundaries...</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="flex items-center text-[10px] font-semibold text-slate-400">
                        <span class="sr-only">Filter heatmap by year</span>
                        <select data-heatmap-year-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-2.5 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                            <option value="">All years</option>
                            @if (! empty($selectedYear) && ! collect($heatmapPeriods['years'] ?? [])->contains('year', $selectedYear))
                                <option value="{{ $selectedYear }}" selected>{{ $selectedYear }} (0)</option>
                            @endif
                            @foreach (($heatmapPeriods['years'] ?? []) as $yearItem)
                                <option value="{{ $yearItem['year'] }}" @selected(($selectedYear ?? null) === $yearItem['year'])>
                                    {{ $yearItem['year'] }} ({{ number_format($yearItem['count']) }})
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="flex items-center text-[10px] font-semibold text-slate-400">
                        <span class="sr-only">Filter heatmap by month</span>
                        <select data-heatmap-month-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-2.5 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                            <option value="">All months</option>
                            @if (! empty($selectedMonth))
                                <option value="{{ $selectedMonth }}" selected>{{ \Carbon\Carbon::create()->month((int) $selectedMonth)->format('F') }}</option>
                            @endif
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-400">
                        <span class="sr-only">Filter by barangay</span>
                        <select data-barangay-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                            <option value="">All barangays</option>
                        </select>
                    </label>
                </div>
            </div>
            <div class="relative h-[28rem] bg-[#0b2852]">
                <div
                    data-flood-map
                    data-boundary-url="{{ asset('data/davao-barangays.geojson') }}"
                    data-heatmap-url="{{ route('operations.heatmap') }}"
                    data-heatmap-periods='@json($heatmapPeriods ?? ['years' => [], 'months' => []])'
                    data-selected-year="{{ $selectedYear ?? '' }}"
                    data-selected-month="{{ $selectedMonth ?? '' }}"
                    data-heat-points='@json($mapPoints ?? [])'
                    @if (! empty($barangayReportCounts)) data-barangay-counts='@json($barangayReportCounts)' @endif
                    class="h-full w-full"
                ></div>
                <div class="pointer-events-none absolute bottom-0 left-0 right-0 bg-[#071738]/85 px-5 py-3 text-xs text-slate-400">
                    Showing Verified Flood Reports (<code>VERIFIED_ACTIONABLE</code>) clipped to Davao City barangay boundaries
                </div>
            </div>
        </div>

        <div class="operations-feed-card rounded-2xl p-5">
            <div class="operations-card-header mb-5 border-b pb-4">
                <div>
                    <h2 class="whitespace-nowrap text-base font-black tracking-tight text-slate-900 dark:text-white">Verified Flood Report Feed</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">Confirmed flood alerts and official emergency broadcasts</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                    <label for="feed-sort-select" class="sr-only">Sort verified flood reports</label>
                    <div class="relative">
                        <select id="feed-sort-select" class="operations-select appearance-none whitespace-nowrap rounded-lg py-2 pl-3 pr-8 text-xs font-semibold outline-none">
                            <option>Sort: Confidence</option>
                        </select>
                        <svg aria-hidden="true" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.2 7.2a1 1 0 0 1 1.4 0l3.4 3.4 3.4-3.4a1 1 0 1 1 1.4 1.4l-4.1 4.1a1 1 0 0 1-1.4 0L5.2 8.6a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
                    </div>
                    <button type="button" data-broadcast-alert class="inline-flex whitespace-nowrap items-center gap-2 rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white shadow-sm shadow-rose-950/25 transition hover:-translate-y-0.5 hover:bg-rose-500">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10v4h4l5 4V6l-5 4H4Z"/><path d="M17 9a4 4 0 0 1 0 6M19.5 6.5a7.5 7.5 0 0 1 0 11"/></svg>
                        <span>New Broadcast</span>
                    </button>
                </div>
            </div>
            <div class="operations-feed-list max-h-[28rem] space-y-3 overflow-y-auto pr-1">
                @if (! empty($actionablePosts) && $actionablePosts->isNotEmpty())
                    @foreach ($actionablePosts as $post)
                        @php
                            $cs = (float) $post->confidence_score;
                            $isOfficial = $post->discard_reason === 'verified_by_official';
                            $badgeColor = ($post->is_whitelisted_source || $isOfficial) ? 'bg-blue-500' : ($cs >= 0.75 ? 'bg-emerald-500' : ($cs >= 0.50 ? 'bg-amber-400' : 'bg-red-500'));
                            $badgeLabel = 'CS '.number_format($cs, 2).($isOfficial ? ' · official tagged' : ($post->is_whitelisted_source ? ' · verified' : ''));
                        @endphp
                        <article class="operations-feed-item rounded-xl p-4">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-xs font-bold text-blue-300">Brgy. {{ $post->primary_barangay ?? 'Davao City' }}</h3>
                                <span class="{{ $badgeColor }} rounded px-2 py-1 text-[9px] font-bold text-white">{{ $badgeLabel }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($post->cleaned_text ?: $post->full_text, 160) }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-200 pt-3 dark:border-slate-800">
                                <span class="tabular-nums text-xs text-slate-500 dark:text-slate-400">{{ $post->author }} · {{ $post->raw_timestamp ?: '' }}</span>
                                @if ($cs >= 0.75)
                                    <button
                                        type="button"
                                        data-broadcast-alert
                                        data-post-id="{{ $post->id }}"
                                        data-post-barangay="{{ $post->primary_barangay ?? '' }}"
                                        data-post-text="{{ e(\Illuminate\Support\Str::limit($post->cleaned_text ?: $post->full_text, 220)) }}"
                                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-blue-500"
                                    >
                                        Broadcast Alert
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <section id="official-tagging-queue" class="operations-queue-card mt-6 rounded-2xl p-5 sm:p-6">
        <div class="operations-card-header flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">Barangay Official Tagging Queue — Flood Mentioned / No Urgency</h2>
                    <span class="tabular-nums rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300">
                        {{ number_format($noUrgencyTotalCount ?? 0) }} Awaiting Barangay Official Review
                    </span>
                </div>
                <p class="mt-2 max-w-3xl text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    Cleaned posts classified by the model as <code class="operations-code">FLOOD_MENTION_NO_URGENCY</code>. Barangay Officials can confirm the barangay location and tag a post as flood-related to promote it to <code class="operations-code">VERIFIED_ACTIONABLE</code> and plot it on the heatmap above.
                </p>
            </div>

            <form method="GET" action="{{ route('operations') }}#official-tagging-queue" class="flex flex-wrap items-center gap-2">
                <label for="review-barangay-select" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Filter by barangay:</label>
                <select
                    id="review-barangay-select"
                    name="review_barangay"
                    onchange="this.form.submit()"
                    class="operations-select rounded-lg px-3 py-2 text-xs font-bold outline-none focus:border-cyan-400"
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
                <article class="operations-queue-item flex flex-col justify-between rounded-xl border p-4">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($watchPost->post_url)
                                    <a href="{{ $watchPost->post_url }}" target="_blank" rel="noopener noreferrer"                                     class="text-sm font-bold text-blue-700 transition hover:text-cyan-500 hover:underline dark:text-blue-200 dark:hover:text-cyan-300">
                                        {{ $watchPost->author }}
                                    </a>
                                @else
                                    <span class="text-sm font-bold text-slate-800 dark:text-blue-200">{{ $watchPost->author }}</span>
                                @endif
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300">
                                    NO URGENCY MENTIONED
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="tabular-nums rounded bg-blue-100 px-2 py-1 font-mono text-[10px] font-bold text-blue-700 dark:bg-blue-950/90 dark:text-cyan-300">
                                    P_rel {{ number_format((float) $watchPost->p_rel, 2) }}
                                </span>
                                <span class="tabular-nums text-xs text-slate-500 dark:text-slate-400">{{ $watchPost->raw_timestamp ?: '' }}</span>
                            </div>
                        </div>

                        <p class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($watchPost->cleaned_text ?: $watchPost->full_text, 280) }}</p>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex flex-wrap items-center gap-1.5">
                                @php
                                    $locs = ! empty($watchPost->extracted_locations) ? $watchPost->extracted_locations : ($watchPost->detected_locations ?? []);
                                @endphp
                                @if (! empty($locs))
                                    @foreach (array_slice($locs, 0, 3) as $loc)
                                        <span class="rounded bg-orange-100 px-2 py-1 text-[10px] font-bold text-orange-700 dark:bg-orange-500/20 dark:text-orange-300">
                                            {{ $loc }}
                                        </span>
                                    @endforeach
                                @endif
                                @if ($watchPost->river_basin)
                                    <span class="rounded bg-cyan-100 px-2 py-1 text-[10px] font-semibold text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300">
                                        {{ $watchPost->river_basin }}
                                    </span>
                                @endif
                            </div>

                            @if ($watchPost->post_url)
                                <a href="{{ $watchPost->post_url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-cyan-300 transition hover:text-cyan-200 hover:underline">
                                    Open on Facebook ↗
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 border-t border-slate-200 pt-4 dark:border-blue-900/50">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <form method="POST" action="{{ route('harvesting.posts.verify', $watchPost) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Barangay:</label>
                                <select
                                    name="barangay"
                                    class="operations-select rounded-lg px-2.5 py-1.5 text-xs font-bold text-cyan-700 outline-none focus:border-cyan-400 dark:text-cyan-200"
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
                                    Tag as Flood Related (Add to Heatmap)
                                </button>
                            </form>

                            <form method="POST" action="{{ route('harvesting.posts.dismiss', $watchPost) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-lg border border-rose-500/40 bg-rose-500/15 px-2.5 py-1.5 text-[10px] font-bold text-rose-300 transition hover:bg-rose-500/30"
                                >
                                    Dismiss
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="operations-empty-state col-span-full">
                    <svg aria-hidden="true" class="h-10 w-10 text-slate-400 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18.5v-13Z"/><path d="M8 8h8M8 12h5m-5 4h3"/></svg>
                    <p class="mt-3 text-sm font-bold text-slate-700 dark:text-slate-200">No pending official reviews</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No <code class="operations-code">FLOOD_MENTION_NO_URGENCY</code> posts match this barangay filter.</p>
                </div>
            @endforelse
        </div>

        @if (! empty($noUrgencyPosts) && $noUrgencyPosts->hasPages())
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-blue-900/50 pt-3 text-xs text-slate-400">
                <span class="tabular-nums">
                Showing <strong class="text-slate-800 dark:text-slate-100">{{ $noUrgencyPosts->firstItem() }}</strong>–<strong class="text-slate-800 dark:text-slate-100">{{ $noUrgencyPosts->lastItem() }}</strong> of <strong class="text-slate-800 dark:text-slate-100">{{ number_format($noUrgencyPosts->total()) }}</strong> posts awaiting review
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

    <div data-broadcast-modal class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-950/80 p-5 backdrop-blur-sm" style="z-index: 99999;">
        <form
            data-broadcast-form
            method="POST"
            action="{{ route('operations.broadcast') }}"
            class="relative z-[100000] w-full max-w-lg rounded-2xl border border-blue-900/60 bg-[#071738] p-6 shadow-2xl text-white"
            style="z-index: 100000;"
        >
            @csrf
            <input type="hidden" name="social_post_id" id="broadcast-post-id" value="">

            <div class="flex items-center justify-between border-b border-blue-900/50 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/20 text-red-400">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10v4h4l5 4V6l-5 4H4Z"/><path d="M17 9a4 4 0 0 1 0 6M19.5 6.5a7.5 7.5 0 0 1 0 11"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-black text-white">Broadcast Emergency Alert</h2>
                        <p class="text-[10px] text-slate-400">Pushes immediate banner and notification to Citizen App users</p>
                    </div>
                </div>
                <button type="button" data-close-broadcast class="cursor-pointer text-2xl font-bold text-slate-400 hover:text-white transition" aria-label="Close modal">×</button>
            </div>

            <div class="mt-4 space-y-3.5">
                <div>
                    <label for="broadcast-title" class="block text-xs font-bold text-slate-300">Alert Title</label>
                    <input
                        type="text"
                        name="title"
                        id="broadcast-title"
                        required
                        maxlength="160"
                        placeholder="e.g. Flash Flood Warning: Bucana"
                        class="mt-1 w-full rounded-xl border border-blue-900/60 bg-[#040e24] px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400"
                    >
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="broadcast-severity" class="block text-xs font-bold text-slate-300">Urgency Level</label>
                        <select
                            name="severity"
                            id="broadcast-severity"
                            required
                            class="mt-1 w-full rounded-xl border border-blue-900/60 bg-[#040e24] px-3 py-2 text-xs text-white outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400"
                        >
                            <option value="CRITICAL" selected>CRITICAL (Immediate Flood)</option>
                            <option value="WARNING">WARNING (Rising Water)</option>
                            <option value="ADVISORY">ADVISORY (Weather Monitor)</option>
                            <option value="INFO">INFO / ALL CLEAR</option>
                        </select>
                    </div>

                    <div>
                        <label for="broadcast-barangay" class="block text-xs font-bold text-slate-300">Target Area</label>
                        <select
                            name="barangay_id"
                            id="broadcast-barangay"
                            class="mt-1 w-full rounded-xl border border-blue-900/60 bg-[#040e24] px-3 py-2 text-xs text-white outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400"
                        >
                            <option value="">Davao City (Citywide)</option>
                            @foreach (\App\Models\Barangay::orderBy('name')->get() as $brgy)
                                <option value="{{ $brgy->id }}">{{ $brgy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="broadcast-message" class="block text-xs font-bold text-slate-300">Alert Message</label>
                    <textarea
                        name="message"
                        id="broadcast-message"
                        required
                        maxlength="2000"
                        rows="3"
                        class="mt-1 w-full rounded-xl border border-blue-900/60 bg-[#040e24] px-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400"
                        placeholder="Describe water level, affected streets, evacuation orders, or hotline contact..."
                    ></textarea>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2 border-t border-blue-900/50 pt-3">
                <button
                    type="button"
                    data-close-broadcast
                    class="rounded-xl border border-blue-900/60 bg-blue-950/40 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-blue-900/50 transition"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    data-broadcast-submit
                    class="rounded-xl bg-red-600 px-5 py-2 text-xs font-bold text-white shadow-lg shadow-red-950/30 hover:bg-red-500 transition"
                >
                    Send Broadcast Alert
                </button>
            </div>
        </form>
    </div>
    </div>
</x-layouts.app>