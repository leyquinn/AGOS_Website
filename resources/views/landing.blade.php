<x-layouts.guest>
    <!-- Section 1: Hero (Home) -->
    <section id="home" data-scroll-fade class="relative isolate w-full overflow-hidden px-5 sm:px-8 lg:px-12 pt-32 sm:pt-40 pb-24 min-h-screen flex flex-col justify-center items-center text-center">
        <!-- Replace this temporary background layer with your hero image later. -->
        <div data-landing-hero-bg class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_50%_35%,rgba(37,99,235,0.42),transparent_38%),linear-gradient(135deg,#071738_0%,#031027_55%,#020b1a_100%)]"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-30 [background-image:linear-gradient(rgba(96,165,250,0.14)_1px,transparent_1px),linear-gradient(90deg,rgba(96,165,250,0.14)_1px,transparent_1px)] [background-size:42px_42px]"></div>
        <div data-landing-hero-overlay class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-[#031027]/10 via-[#031027]/30 to-[#031027]"></div>

        <span class="landing-hero-badge inline-flex items-center gap-2 text-[11px] uppercase tracking-[0.16em] text-blue-100 font-bold px-4 py-1.5 bg-blue-950/80 border border-blue-300/60 rounded-full mb-8 shadow-sm">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.8)]"></span>
            Flood intelligence, built for the barangay
        </span>
        
        <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight text-white max-w-5xl leading-tight">
            From flood reports to <span class="text-blue-400">clearer decisions.</span>
        </h1>
        
        <p class="mt-6 text-base sm:text-lg md:text-xl text-slate-300 max-w-3xl font-normal leading-relaxed">
            AGOS turns noisy, code-switched community reports into validated flood intelligence that helps local response teams act with confidence.
        </p>

        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="{{ asset('thesis_paper.pdf') }}" target="_blank" rel="noopener" class="landing-action w-full bg-white px-8 py-3.5 font-bold text-slate-900 shadow-lg hover:border-blue-600 hover:bg-blue-600 hover:text-white sm:w-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Read Thesis (PDF)
            </a>
            <a href="{{ route('login') }}" class="landing-action w-full border border-blue-800/50 bg-blue-950/60 px-8 py-3.5 font-medium text-white shadow-lg shadow-blue-950/10 hover:border-blue-500 hover:bg-blue-600 sm:w-auto">
                Explore Dashboard
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

        <div class="mt-20 flex flex-wrap justify-center gap-8 text-[11px] uppercase tracking-widest text-slate-400 font-medium">
            <span>EN / TL / CEB ready</span>
            <span>Unified NLP pipeline</span>
            <span>Consensus-checked signals</span>
        </div>
        <div class="mt-12 grid w-full max-w-3xl grid-cols-1 gap-3 text-left sm:grid-cols-3">
            <div class="rounded-2xl border border-blue-800/50 bg-[#071738]/70 p-4 backdrop-blur-sm">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">Signal status</span>
                <span class="flex items-center gap-2 text-sm font-semibold text-white"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Monitoring active</span>
            </div>
            <div class="rounded-2xl border border-blue-800/50 bg-[#071738]/70 p-4 backdrop-blur-sm">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">Built for</span>
                <span class="text-sm font-semibold text-white">DRRMO &amp; LGU teams</span>
            </div>
            <div class="rounded-2xl border border-blue-800/50 bg-[#071738]/70 p-4 backdrop-blur-sm">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">Core output</span>
                <span class="text-sm font-semibold text-white">Verified local context</span>
            </div>
        </div>
    </section>

    <!-- Public Flood Heatmap (Read-Only Access) -->
    <section id="heatmap" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <div class="mb-10 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400 font-bold">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Public Flood Heatmap
                </span>
                <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl font-black text-white max-w-3xl leading-tight">
                    Verified flood reports, open to the public.
                </h2>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-400">
                    Anyone can view validated barangay flood reports and hotspots without signing in. Submitting new reports is restricted to registered citizen accounts.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-100 px-3.5 py-1.5 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    Read-Only Public View
                </span>
                <span data-heatmap-badge class="inline-flex items-center rounded-full border border-blue-500/30 bg-blue-100 px-3.5 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                    {{ number_format($validatedEventsCount ?? 0) }} Verified Reports · {{ $barangaysAffectedCount ?? 0 }} Barangays
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="overflow-hidden rounded-3xl border border-blue-900/50 bg-[#071738]/80 shadow-2xl shadow-blue-950/20 lg:col-span-8">
                <div class="flex flex-col gap-3 border-b border-blue-900/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white">Davao City Barangay Flood Heatmap</h3>
                        <p data-map-status class="mt-1 text-xs text-slate-400">Loading Davao City barangay boundaries...</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="flex items-center text-xs font-semibold text-slate-400">
                            <span class="sr-only">Filter heatmap by year</span>
                            <select data-heatmap-year-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-xs text-slate-200 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
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
                        <label class="flex items-center text-xs font-semibold text-slate-400">
                            <span class="sr-only">Filter heatmap by month</span>
                            <select data-heatmap-month-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-xs text-slate-200 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="">All months</option>
                                @if (! empty($selectedMonth))
                                    <option value="{{ $selectedMonth }}" selected>{{ \Carbon\Carbon::create()->month((int) $selectedMonth)->format('F') }}</option>
                                @endif
                            </select>
                        </label>
                        <label class="flex items-center text-xs font-semibold text-slate-400">
                            <span class="sr-only">Filter by barangay</span>
                            <select data-barangay-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-xs text-slate-200 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="">All barangays</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="relative h-[26rem] sm:h-[30rem] bg-[#0b2852]">
                    <div
                        data-flood-map
                        data-boundary-url="{{ asset('data/davao-barangays.geojson') }}"
                        data-heatmap-url="{{ route('public.heatmap') }}"
                        data-heatmap-periods='@json($heatmapPeriods ?? ['years' => [], 'months' => []])'
                        data-selected-year="{{ $selectedYear ?? '' }}"
                        data-selected-month="{{ $selectedMonth ?? '' }}"
                        data-heat-points='@json($mapPoints ?? [])'
                        @if (! empty($barangayReportCounts)) data-barangay-counts='@json($barangayReportCounts)' @endif
                        class="h-full w-full"
                    ></div>
                    <div class="pointer-events-none absolute bottom-3 left-3 right-3 z-[400] flex flex-wrap items-center justify-between gap-2 rounded-xl border border-blue-900/50 bg-[#031027]/85 px-4 py-2.5 text-[11px] text-slate-300 backdrop-blur-sm">
                        <span>Showing verified flood alerts &amp; reports clipped to Davao City boundaries</span>
                        <span class="flex items-center gap-3 font-semibold">
                            <span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-rose-500"></i> High</span>
                            <span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-amber-400"></i> Moderate</span>
                            <span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-sky-400"></i> Low</span>
                        </span>
                    </div>
                </div>
            </div>

            <aside class="flex flex-col justify-between rounded-3xl border border-blue-900/50 bg-[#071738]/80 p-6 shadow-2xl shadow-blue-950/20 lg:col-span-4">
                <div>
                    <div class="flex items-center justify-between gap-2 border-b border-blue-900/50 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white">Verified Public Reports</h3>
                            <p class="mt-0.5 text-xs text-slate-400">Validated signals visible without an account</p>
                        </div>
                        <span class="rounded-lg border border-blue-800/60 bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:bg-blue-950/80 dark:text-blue-300">Open Data</span>
                    </div>

                    <div data-public-reports-list class="mt-4 max-h-[19rem] space-y-3 overflow-y-auto pr-1">
                        @forelse (($publicReports ?? []) as $item)
                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 dark:border-blue-900/50 dark:bg-[#0d2b5b]/70">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-blue-700 dark:text-blue-300">Brgy. {{ $item['barangay'] }}</span>
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">CS {{ $item['confidence'] }}</span>
                                </div>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ $item['excerpt'] }}</p>
                                <div class="mt-2 flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400">
                                    <span>{{ $item['sourceLabel'] }}</span>
                                    <span>{{ $item['reportedAt'] }}</span>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-xs text-slate-500 dark:border-blue-800/60 dark:text-slate-400">
                                No verified flood reports recorded for the selected month yet. Switch the year or month filter above to explore past reports.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-amber-500/30 bg-amber-50 p-4 dark:bg-amber-500/10">
                    <div class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V7a4 4 0 1 1 8 0m-8 4h8a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h6Z"></path></svg>
                        <div>
                            <h4 class="text-xs font-bold text-amber-800 dark:text-amber-300">Posting Requires a Citizen Account</h4>
                            <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                                Guests may browse verified flood reports publicly, but creating a new report requires signing in to verify community submissions.
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2.5">
                        @if (auth()->check() && auth()->user()->role === 'citizen')
                            <a href="{{ route('citizen.report') }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-500">
                                Post a Flood Report
                            </a>
                        @else
                            <a href="{{ route('citizen.login') }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-500">
                                Sign In to Report
                            </a>
                            <a href="{{ route('citizen.register') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-100 dark:border-blue-800/70 dark:bg-[#031027] dark:text-slate-200 dark:hover:bg-blue-900/40">
                                Register
                            </a>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <!-- Section 2: Problem -->
    <section id="problem" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">The Challenge</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-5 max-w-3xl leading-tight">
            The first signal is not always the clearest one.
        </h2>
        <p class="mb-12 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-16 sm:text-lg">When every minute matters, responders need more than a stream of posts. They need context, confidence, and a location they can trust.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <span class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-red-500/10 text-red-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"></path></svg></span>
                <h3 class="text-sm font-bold text-red-500 uppercase tracking-wider mb-4">Noisy Data</h3>
                <p class="text-base leading-relaxed text-slate-300">Social posts are unstructured, code-switched, and often contain sarcasm or unrelated content.</p>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <span class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 5h8m-8 5h5M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"></path></svg></span>
                <h3 class="text-sm font-bold text-amber-500 uppercase tracking-wider mb-4">Fragmented</h3>
                <p class="text-base leading-relaxed text-slate-300">Legacy systems treat classification and location extraction as disjointed, heavy pipelines.</p>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <span class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.62-2.19a9 9 0 11-3.43-3.43"></path></svg></span>
                <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider mb-4">Unverified</h3>
                <p class="text-base leading-relaxed text-slate-300">Without consensus-checking, LGUs cannot distinguish real events from isolated, unverifiable posts.</p>
            </div>
        </div>
    </section>

    <!-- Section 3: Process -->
    <section id="process" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">How It Works</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-12 sm:mb-16 max-w-3xl leading-tight">
            One unified pipeline, from raw social feed to validated intelligence.
        </h2>

        <div class="mb-5 flex items-center justify-between">
            <button type="button" data-carousel-prev="process-carousel" class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:bg-blue-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40" aria-label="Previous process step">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button type="button" data-carousel-next="process-carousel" class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:bg-blue-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40" aria-label="Next process step">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>

        <div id="process-carousel" data-carousel class="overflow-hidden pb-3 pt-2" aria-label="How it works process steps">
            <div data-carousel-track class="flex gap-4 transition-transform duration-1000 ease-in-out">
            <div data-process-card class="min-w-[85%] shrink-0 snap-start rounded-2xl border border-slate-300 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:bg-blue-50 hover:shadow-xl hover:shadow-blue-500/10 sm:min-w-[45%] lg:min-w-[32%] dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-cyan-400 dark:hover:bg-cyan-400/10">
                <span class="text-xs font-bold text-blue-600 dark:text-cyan-300">01</span>
                <h3 class="mt-4 mb-3 text-base font-bold uppercase text-slate-900 dark:text-white">Harvest the signal</h3>
                <p class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-300">Keyword and hashtag listeners collect dialect-aware flood reports from public feeds across Davao City hotspots.</p>
            </div>
            <div data-process-card class="min-w-[85%] shrink-0 snap-start rounded-2xl border border-slate-300 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:bg-blue-50 hover:shadow-xl hover:shadow-blue-500/10 sm:min-w-[45%] lg:min-w-[32%] dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-cyan-400 dark:hover:bg-cyan-400/10">
                <span class="text-xs font-bold text-blue-600 dark:text-cyan-300">02</span>
                <h3 class="mt-4 mb-3 text-base font-bold uppercase text-slate-900 dark:text-white">Preprocess the noise</h3>
                <p class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-300">A deterministic six-layer cleaner removes commercial ads, normalizes panic text, and deduplicates related posts.</p>
            </div>
            <div data-process-card class="min-w-[85%] shrink-0 snap-start rounded-2xl border border-slate-300 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:bg-blue-50 hover:shadow-xl hover:shadow-blue-500/10 sm:min-w-[45%] lg:min-w-[32%] dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-cyan-400 dark:hover:bg-cyan-400/10">
                <span class="text-xs font-bold text-blue-600 dark:text-cyan-300">03</span>
                <h3 class="mt-4 mb-3 text-base font-bold uppercase text-slate-900 dark:text-white">Understand with multi-task NLP</h3>
                <p class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-300">One shared XLM-RoBERTa encoder classifies flood relevance and extracts barangay entities in one pass.</p>
            </div>
            <div data-process-card class="min-w-[85%] shrink-0 snap-start rounded-2xl border border-slate-300 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:bg-blue-50 hover:shadow-xl hover:shadow-blue-500/10 sm:min-w-[45%] lg:min-w-[32%] dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-cyan-400 dark:hover:bg-cyan-400/10">
                <span class="text-xs font-bold text-blue-600 dark:text-cyan-300">04</span>
                <h3 class="mt-4 mb-3 text-base font-bold uppercase text-slate-900 dark:text-white">Check spatial consensus</h3>
                <p class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-300">K-Means++ compares reports across time and location to separate clustered events from isolated anomalies.</p>
            </div>
            <div data-process-card class="min-w-[85%] shrink-0 snap-start rounded-2xl border border-slate-300 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:bg-blue-50 hover:shadow-xl hover:shadow-blue-500/10 sm:min-w-[45%] lg:min-w-[32%] dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-cyan-400 dark:hover:bg-cyan-400/10">
                <span class="text-xs font-bold text-blue-600 dark:text-cyan-300">05</span>
                <h3 class="mt-4 mb-3 text-base font-bold uppercase text-slate-900 dark:text-white">Deliver validated output</h3>
                <p class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-300">The LGU receives a confidence score, verified barangay location, and concise event summary for action.</p>
            </div>
            </div>
        </div>

        <!-- Formula Banner -->
        <div data-process-formula class="mt-8 flex flex-col items-start justify-between gap-5 rounded-2xl border border-slate-300 bg-white p-6 transition-colors duration-300 md:flex-row md:items-center dark:border-slate-700 dark:bg-slate-900/70">
            <div>
                <h4 class="text-base font-bold text-slate-900 dark:text-white">The Confidence Score Formula</h4>
                <p class="mt-2 font-mono text-base font-semibold text-blue-700 sm:text-lg dark:text-cyan-300">CS = w₁·P(rel) + w₂·D(cluster)</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-400">w₁ = 0.30, w₂ = 0.70 · Source Authority Override: CS = 1.0 for verified accounts</p>
            </div>
            <a href="{{ route('login') }}" class="landing-action shrink-0 border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-sm hover:border-blue-600 hover:bg-blue-600 hover:text-white dark:border-cyan-400/30 dark:bg-slate-900/70 dark:text-cyan-300 dark:hover:border-cyan-400 dark:hover:bg-cyan-400 dark:hover:text-slate-950">
                View Report Detail &rarr;
            </a>
        </div>
    </section>

    <!-- Section 4: Results -->
    <section id="results" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <div class="lg:grid lg:grid-cols-[minmax(15rem,0.75fr)_minmax(0,1.25fr)] lg:items-start lg:gap-x-14">
            <div>
                <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">Evaluation</span>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-5 leading-tight">
                    Better signals lead to better response.
                </h2>
                <p class="mb-12 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-16 sm:text-lg">A quick look at the model’s evaluation results, designed to make the value of validated reporting easy to understand.</p>
            </div>
            <div class="grid grid-cols-2 gap-6 lg:col-start-2">
                <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Accuracy</span>
                    <div class="text-5xl font-black text-white mt-3">0.93</div>
                    <span class="text-xs font-semibold text-emerald-400 mt-2 block">+12% vs Baseline</span>
                </div>
                <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Precision</span>
                    <div class="text-5xl font-black text-white mt-3">0.90</div>
                    <span class="text-xs font-semibold text-emerald-400 mt-2 block">+13% vs Baseline</span>
                </div>
                <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Recall</span>
                    <div class="text-5xl font-black text-white mt-3">0.88</div>
                    <span class="text-xs font-semibold text-emerald-400 mt-2 block">+14% vs Baseline</span>
                </div>
                <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-800/80 rounded-2xl text-center bg-blue-950/20">
                    <span class="text-xs font-bold text-blue-400 uppercase tracking-widest">F1-Score</span>
                    <div class="text-5xl font-black text-blue-400 mt-3">0.89</div>
                    <span class="text-xs font-semibold text-emerald-400 mt-2 block">+14% vs Baseline</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Features (Interfaces) -->
    <section id="features" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">See It In Action</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-5 max-w-3xl leading-tight">
            One trusted picture, from two sides of the story.
        </h2>
        <p class="mb-12 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-16 sm:text-lg">AGOS connects the people who report what they see with the teams who decide what happens next.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <a href="{{ route('login') }}" data-landing-card class="bg-[#071738]/80 border border-blue-900/40 rounded-3xl overflow-hidden flex flex-col justify-between">
                <div class="relative h-64 overflow-hidden bg-[#091b40] p-6">
                    <img src="{{ asset('images/lgu-operations.png') }}" alt="LGU Operations Console preview" class="absolute inset-0 z-10 h-full w-full object-cover" onerror="this.style.display='none'">
                    <div class="absolute inset-x-8 top-8 h-2 rounded-full bg-blue-900/80"></div>
                    <div class="absolute left-8 top-20 h-28 w-2/3 rounded-2xl border border-blue-700/50 bg-blue-600/20"></div>
                    <div class="absolute right-8 top-20 space-y-3">
                        <span class="block h-3 w-20 rounded-full bg-emerald-400/80"></span>
                        <span class="block h-3 w-14 rounded-full bg-blue-400/70"></span>
                        <span class="block h-3 w-24 rounded-full bg-slate-600"></span>
                    </div>
                    <span class="absolute bottom-6 left-8 text-[10px] font-bold uppercase tracking-widest text-blue-300">Live operations view</span>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-white mb-2">LGU Operations Console</h3>
                    <p class="text-slate-400 text-sm mb-6">Signal strip, live map, and model analytics for DRRMO personnel</p>
                    <span class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2">
                        Launch Console &rarr;
                    </span>
                </div>
            </a>

            <a href="{{ route('citizen.login') }}" data-landing-card class="bg-[#071738]/80 border border-blue-900/40 rounded-3xl overflow-hidden flex flex-col justify-between">
                <div class="relative h-64 overflow-hidden bg-[#091b40] p-6">
                    <img src="{{ asset('images/citizen-reporting-app.png') }}" alt="Citizen Reporting App preview" class="absolute inset-0 z-10 h-full w-full object-cover" onerror="this.style.display='none'">
                    <div class="absolute left-1/2 top-6 h-48 w-28 -translate-x-1/2 rounded-[1.5rem] border-4 border-blue-800 bg-[#071738] p-3 shadow-2xl shadow-blue-950/50">
                        <span class="mb-4 block h-2 w-10 rounded-full bg-slate-600 mx-auto"></span>
                        <span class="mb-3 block h-16 rounded-xl bg-blue-600/30"></span>
                        <span class="mb-2 block h-2 w-16 rounded-full bg-slate-500"></span>
                        <span class="block h-2 w-20 rounded-full bg-slate-700"></span>
                    </div>
                    <span class="absolute bottom-6 left-8 text-[10px] font-bold uppercase tracking-widest text-blue-300">Community reporting view</span>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-white mb-2">Citizen Reporting App</h3>
                    <p class="text-slate-400 text-sm mb-6">Mobile-first interface for residents to submit reports and view validated alerts</p>
                    <span class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2">
                        Launch App &rarr;
                    </span>
                </div>
            </a>
        </div>
    </section>

    <!-- Section 6: Team -->
    <section id="team" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <div class="max-w-4xl">
            <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">The Researchers</span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-2 leading-tight">
                Research designed for local action.
            </h2>
            <p class="text-xl sm:text-2xl font-bold text-slate-300 mb-5">Bachelor of Science in Computer Science</p>
            <p class="text-base font-semibold text-blue-300 mb-4">Ateneo de Davao University • Computer Studies Cluster</p>
            <p class="mb-16 max-w-2xl text-base leading-relaxed text-slate-400">A student-built research platform focused on making local flood intelligence more useful, explainable, and actionable.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500/20 to-cyan-400/10 border border-blue-400/70 text-blue-300 font-bold text-lg rounded-full mx-auto flex items-center justify-center mb-6">HC</div>
                <h3 class="text-lg font-bold text-white">Harley Love N. Cuba</h3>
                <p class="mt-3 text-sm text-slate-400">Platform implementation and database</p>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">Researcher</span>
            </div>

            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500/20 to-cyan-400/10 border border-blue-400/70 text-blue-300 font-bold text-lg rounded-full mx-auto flex items-center justify-center mb-6">FE</div>
                <h3 class="text-lg font-bold text-white">Franz Sedrick F. Embuscado</h3>
                <p class="mt-3 text-sm text-slate-400">Model engineering</p>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">Researcher</span>
            </div>

            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500/20 to-cyan-400/10 border border-blue-400/70 text-blue-300 font-bold text-lg rounded-full mx-auto flex items-center justify-center mb-6">JF</div>
                <h3 class="text-lg font-bold text-white">Jan-Aceryl N. Futalan</h3>
                <p class="mt-3 text-sm text-slate-400">NLP &amp; validation</p>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">Researcher</span>
            </div>
        </div>

        <p class="text-xs text-slate-500 uppercase tracking-widest text-center mt-12">
            School of Arts and Sciences • Computer Studies Cluster • November 2026
        </p>
    </section>
</x-layouts.guest>