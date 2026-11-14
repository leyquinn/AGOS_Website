<x-layouts.app title="Sources & Harvesting">
    <div class="harvesting-page w-full" data-harvesting-page data-run-status="{{ $latestRun?->status ?? 'idle' }}">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Manage platform connections, dynamic search queries, and run the Facebook flood scraper directly from AGOS.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-xs font-bold text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/15 px-4 py-3 text-xs font-bold text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        @if (!empty($errors) && $errors->any())
            <div class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/15 px-4 py-3 text-xs font-bold text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        @php
            $isRunning = $latestRun && $latestRun->status === 'running';
            $activeKeywordsCount = $keywords->where('is_active', true)->count();
            $progressWidth = $totalScrapedPosts > 0 ? min(100, max(12, (int) round(($totalScrapedPosts / 1000) * 100))) : 8;
        @endphp

        <section class="grid gap-5 lg:grid-cols-12" aria-label="Platform connections">
            <article class="harvest-panel lg:col-span-4 rounded-2xl p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-black tracking-tight">Facebook Scraper</h2>
                    @if ($isRunning)
                        <span data-scraper-badge class="harvest-badge animate-pulse bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-300">Scraping Now…</span>
                    @elseif ($latestRun && $latestRun->status === 'failed')
                        <span data-scraper-badge class="harvest-badge bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">Last Run Failed</span>
                    @else
                        <span data-scraper-badge class="harvest-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Connected</span>
                    @endif
                </div>
                <p class="mt-6 text-xs font-semibold text-slate-500 dark:text-slate-400">Scraped content collected in database</p>
                <div class="mt-2 h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                    <div class="h-2 rounded-full bg-blue-600" style="width: {{ $progressWidth }}%"></div>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                    <span data-total-scraped>{{ number_format($totalScrapedPosts) }} posts collected</span>
                    <span data-last-scrape-time>
                        @if ($latestRun?->finished_at)
                            last scrape {{ $latestRun->finished_at->diffForHumans() }}
                        @elseif ($latestRun?->started_at)
                            started {{ $latestRun->started_at->diffForHumans() }}
                        @else
                            no scrape runs yet
                        @endif
                    </span>
                </div>
            </article>

            <article class="harvest-panel lg:col-span-8 rounded-2xl p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-black tracking-tight">Run Facebook Flood Scraper</h2>
                    <span class="harvest-badge bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ $activeKeywordsCount }} active {{ Str::plural('query', $activeKeywordsCount) }}</span>
                </div>

                <form method="POST" action="{{ route('harvesting.run') }}" class="mt-5 space-y-3">
                    @csrf
                    <div>
                        <label for="quick-query" class="block text-xs font-semibold text-slate-600 dark:text-slate-300">
                            Type a specific query to scrape now (or leave blank to scrape all enabled listeners below):
                        </label>
                        <input
                            id="quick-query"
                            name="quick_query"
                            type="text"
                            placeholder="e.g. baha Matina Crossing or #bahaDavao"
                            class="harvest-input mt-2 w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                            <span>Scrolls:</span>
                            <select name="scrolls" class="harvest-select rounded-lg px-2.5 py-2 text-xs font-bold outline-none">
                                <option value="2">2 scrolls (Quick test)</option>
                                <option value="5" selected>5 scrolls</option>
                                <option value="15">15 scrolls</option>
                                <option value="50">50 scrolls (Deep)</option>
                            </select>
                        </label>

                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                            <span>Browser:</span>
                            <select name="browser_mode" class="harvest-select rounded-lg px-2.5 py-2 text-xs font-bold outline-none">
                                <option value="visible" selected>Visible Chrome</option>
                                <option value="headless">Headless</option>
                            </select>
                        </label>

                        <button
                            type="submit"
                            class="ml-auto inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-500"
                        >
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4.5 3.8a1 1 0 0 1 1.53-.85l9.1 6.2a1 1 0 0 1 0 1.7l-9.1 6.2A1 1 0 0 1 4.5 16.2V3.8Z"/></svg>
                            <span>Run Scraper Now</span>
                        </button>
                    </div>
                </form>

                @if ($isRunning)
                    <form method="POST" action="{{ route('harvesting.stop') }}" class="mt-2 flex justify-end">
                        @csrf
                        <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-rose-500">
                            Stop Active Run
                        </button>
                    </form>
                @endif
            </article>
        </section>

        @if ($latestRun)
            <section class="mt-5 rounded-2xl border border-blue-900/50 bg-[#071738]/90 p-4 text-slate-200 shadow-lg">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs font-black uppercase tracking-wider text-blue-300">
                            Scraper Run #{{ $latestRun->id }} — <span data-run-status-label>{{ strtoupper($latestRun->status) }}</span>
                        </h2>
                        <span class="text-[10px] text-slate-400">
                            Queries: {{ implode(', ', $latestRun->queries_json ?? []) }}
                        </span>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-300" data-run-stats>
                        Collected in run: {{ $latestRun->posts_collected }} ({{ $latestRun->new_posts_saved }} new)
                    </span>
                </div>
                <pre data-scraper-log class="mt-3 max-h-44 overflow-y-auto rounded-xl bg-slate-950/90 p-3 font-mono text-[10px] leading-relaxed text-slate-300">{{ $latestRun->log_excerpt ?: 'Waiting for scraper log output...' }}</pre>
            </section>
        @endif

        {{-- ── Sequential Pipeline Stage Stepper ── --}}
        <div class="harvest-pipeline mt-5 rounded-2xl p-4 sm:p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-black uppercase tracking-wider text-blue-600 dark:text-blue-300">Sequential Pipeline</span>
                    <span class="harvest-status-pill {{ ($pendingCleanCount ?? 0) > 0 ? 'is-emerald' : (($pendingModelCount ?? 0) > 0 ? 'is-blue' : 'is-neutral') }}">
                        @if (($pendingCleanCount ?? 0) > 0)
                            Stage 2 Active: {{ $pendingCleanCount }} Scraped Posts Awaiting Flood Cleaner
                        @elseif (($pendingModelCount ?? 0) > 0)
                            Stage 3 Active: {{ $pendingModelCount }} Clean Posts Awaiting XLM-RoBERTa Model
                        @else
                            All Posts Up to Date (Run Step 1 Scraper to harvest new flood posts)
                        @endif
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="harvest-step {{ $isRunning ? 'is-active-blue animate-pulse' : 'is-inactive' }}">
                        <span>Step 1: Scrape</span>
                    </span>
                    <svg aria-hidden="true" class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="m7.2 4.3 5.7 5.7-5.7 5.7 1.4 1.4 7.1-7.1-7.1-7.1-1.4 1.4Z"/></svg>
                    <span class="harvest-step {{ ($canRunCleaner ?? false) ? 'is-active-emerald' : 'is-inactive' }}">
                        <span>Step 2: Clean {{ ($pendingCleanCount ?? 0) > 0 ? '('.$pendingCleanCount.')' : '' }}</span>
                    </span>
                    <svg aria-hidden="true" class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="m7.2 4.3 5.7 5.7-5.7 5.7 1.4 1.4 7.1-7.1-7.1-7.1 1.4-1.4Z"/></svg>
                    <span class="harvest-step {{ ($canRunModel ?? false) ? 'is-active-blue' : 'is-inactive' }}">
                        <span>Step 3: Model {{ ($pendingModelCount ?? 0) > 0 ? '('.$pendingModelCount.')' : '' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <section class="harvest-panel mt-5 rounded-2xl p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black tracking-tight">Pipeline Queue &amp; Flood Cleaner</h2>
                </div>

                <form method="POST" action="{{ route('harvesting.clean') }}" class="flex items-center gap-2.5">
                    @csrf
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="strict" value="1" class="h-4 w-4 rounded border-slate-400 text-emerald-600" {{ !($canRunCleaner ?? false) ? 'disabled' : '' }}>
                        <span>Strict filter</span>
                    </label>
                    @if ($canRunCleaner ?? false)
                        <button type="submit" class="flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3v4m10-4v4M5 9h14M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="m8 14 2 2 5-5"/></svg>
                            <span>Run Flood Cleaner Now</span>
                            <span class="rounded-full bg-emerald-800/80 px-2 py-0.5 text-[10px] font-black text-emerald-200">{{ $pendingCleanCount }} new</span>
                        </button>
                    @else
                        <button type="button" disabled class="harvest-locked-button cursor-not-allowed rounded-xl px-3.5 py-2.5 text-xs font-bold shadow-sm" title="Cleaner locked. Run the Facebook Scraper first to collect new posts.">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <span>Cleaner Locked (No new posts)</span>
                        </button>
                    @endif
                </form>
            </div>

            @php
                $retentionRate = $totalScrapedPosts > 0 ? round(($keptPostsCount / $totalScrapedPosts) * 100, 1) : 0;
            @endphp

            <div class="harvest-kpi-grid mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Scraped Input', 'value' => number_format($totalScrapedPosts).' posts', 'class' => 'text-blue-700 dark:text-blue-400'],
                    ['label' => 'Kept (Clean Flood Posts)', 'value' => number_format($keptPostsCount).' kept', 'class' => 'text-emerald-500'],
                    ['label' => 'Removed Noise / Ads / Dups', 'value' => number_format($removedPostsCount).' removed', 'class' => 'text-rose-400'],
                    ['label' => 'Cleaner Retention Rate', 'value' => $retentionRate.'%', 'class' => 'text-cyan-300'],
                ] as $metric)
                    <div class="harvest-kpi">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $metric['label'] }}</p>
                        <p class="mt-1 tabular-nums text-xl font-black {{ $metric['class'] }}">{{ $metric['value'] }}</p>
                    </div>
                @endforeach
            </div>

            @if (! empty($removalBreakdown))
                <div class="mt-5 border-t border-blue-900/30 pt-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Noise Removed by Cleaner Layer:</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($removalBreakdown as $category => $count)
                            <span class="rounded-lg border border-rose-500/30 bg-rose-500/15 px-2.5 py-1 text-[10px] font-bold text-rose-300">
                                {{ $category }}: -{{ $count }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <section class="harvest-panel mt-5 rounded-2xl p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-lg font-black tracking-tight">Multi-Task XLM-RoBERTa + K-Means++ Intelligence</h2>
                        @if ($checkpointReady ?? false)
                            <span class="harvest-badge border border-emerald-500/40 bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">
                                <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-current align-middle"></span>Checkpoint Ready (best_model.pt)
                            </span>
                        @else
                            <span class="harvest-badge border border-amber-500/40 bg-amber-500/15 text-amber-700 dark:text-amber-300">
                                <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-current align-middle"></span>Checkpoint Missing in intelligence/model/checkpoints/
                            </span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('harvesting.analyze') }}" class="flex flex-wrap items-center gap-2.5">
                    @csrf
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <span>Scope:</span>
                        <select name="scope" class="harvest-select rounded-lg px-2.5 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500" {{ !($canRunModel ?? false) ? 'disabled' : '' }}>
                            <option value="pending" selected>Pending Clean Posts ({{ $pendingModelCount ?? 0 }})</option>
                            <option value="kept">All Kept / Clean posts ({{ $keptPostsCount }})</option>
                            <option value="all">All scraped posts ({{ $totalScrapedPosts }})</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <span>Threshold:</span>
                        <select name="threshold" class="harvest-select rounded-lg px-2.5 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500" {{ !($canRunModel ?? false) ? 'disabled' : '' }}>
                            <option value="0.50">P_rel ≥ 0.50</option>
                            <option value="0.60">P_rel ≥ 0.60</option>
                            <option value="0.70">P_rel ≥ 0.70</option>
                        </select>
                    </label>
                    @if ($canRunModel ?? false)
                        <button type="submit" class="flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-500">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a7 7 0 0 0-7 7v4l-2 2h18l-2-2v-4a7 7 0 0 0-7-7Z"/><path d="M9 20h6"/></svg>
                            <span>Run XLM-RoBERTa Model Now</span>
                            <span class="rounded-full bg-blue-800/80 px-2 py-0.5 text-[10px] font-black text-blue-200">{{ $pendingModelCount }} clean</span>
                        </button>
                    @else
                        <button type="button" disabled class="harvest-locked-button cursor-not-allowed rounded-xl px-3.5 py-2.5 text-xs font-bold shadow-sm" title="{{ ($pendingCleanCount ?? 0) > 0 ? 'Model locked. Run Flood Cleaner first.' : 'Model locked. All posts analyzed.' }}">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <span>Model Locked ({{ ($pendingCleanCount ?? 0) > 0 ? 'Clean '.$pendingCleanCount.' pending posts first' : 'All posts analyzed' }})</span>
                        </button>
                    @endif
                </form>
            </div>

            <div class="harvest-kpi-grid mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Posts Analyzed by XLM-R', 'value' => number_format($analyzedPostsCount ?? 0).' / '.number_format($totalScrapedPosts), 'class' => 'text-blue-700 dark:text-blue-400'],
                    ['label' => 'Verified Actionable (Emergency)', 'value' => number_format($actionablePostsCount ?? 0).' actionable', 'class' => 'text-emerald-500'],
                    ['label' => 'Flood Watch / HITL (No Urgency)', 'value' => number_format($earlyWarningPostsCount ?? 0).' watch', 'class' => 'text-amber-400'],
                    ['label' => 'Avg. Confidence Score (CS)', 'value' => number_format($avgConfidenceScore ?? 0, 2), 'class' => 'text-cyan-300'],
                ] as $metric)
                    <div class="harvest-kpi">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $metric['label'] }}</p>
                        <p class="mt-1 tabular-nums text-xl font-black {{ $metric['class'] }}">{{ $metric['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mt-5 grid gap-5 xl:grid-cols-2">
            <section class="harvest-panel rounded-2xl p-4 sm:p-5">
                <form method="POST" action="{{ route('harvesting.keywords.store') }}">
                    @csrf
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black tracking-tight">Keyword &amp; hashtag listeners</h2>
                        <button type="submit" class="harvest-add-button inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold transition"><span class="text-base leading-none">+</span> Add</button>
                    </div>
                    <div class="mt-5 flex gap-3">
                        <label class="sr-only" for="listener-keyword">Add a keyword or hashtag</label>
                        <input
                            id="listener-keyword"
                            name="keyword"
                            type="text"
                            required
                            placeholder="e.g. #bahaDavao or baha Matina Pangi"
                            class="harvest-input min-w-0 flex-1 rounded-xl px-3 py-2.5 text-sm outline-none"
                        >
                        <label class="sr-only" for="listener-platform">Listener platform</label>
                        <select id="listener-platform" name="platform" class="harvest-select w-24 rounded-xl px-2 py-2.5 text-xs font-semibold outline-none focus:ring-2 focus:ring-blue-500 sm:w-28">
                            <option value="Facebook">Facebook</option>
                        </select>
                    </div>
                </form>

                <div class="harvest-row-list mt-5 space-y-2">
                    @forelse ($keywords as $listener)
                        <div class="harvest-row flex items-center justify-between gap-3 rounded-xl px-3.5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100 {{ $listener->is_active ? '' : 'line-through opacity-60' }}">
                                    {{ $listener->keyword }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $listener->platform }} · {{ $listener->matches_count }} {{ Str::plural('match', $listener->matches_count) }} collected
                                    @if ($listener->last_scraped_at)
                                        · scraped {{ $listener->last_scraped_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <form method="POST" action="{{ route('harvesting.keywords.toggle', $listener) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="harvest-toggle relative h-5 w-10 shrink-0 rounded-full transition {{ $listener->is_active ? 'is-on' : 'is-off' }}"
                                        title="{{ $listener->is_active ? 'Pause listener' : 'Enable listener' }}"
                                        aria-label="{{ $listener->is_active ? 'Disable' : 'Enable' }} {{ $listener->keyword }}"
                                    >
                                        <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-all {{ $listener->is_active ? 'right-0.5' : 'left-0.5' }}"></span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('harvesting.keywords.destroy', $listener) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="harvest-remove rounded-md px-2 py-1 text-sm font-medium transition" title="Remove keyword">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">No keyword listeners added yet. Add one above to start scraping.</p>
                    @endforelse
                </div>
            </section>

            <section class="harvest-panel rounded-2xl p-4 sm:p-5">
                <form method="POST" action="{{ route('harvesting.sources.store') }}">
                    @csrf
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black tracking-tight">Verified source whitelist</h2>
                        <button type="submit" class="harvest-add-button inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold transition"><span class="text-base leading-none">+</span> Add</button>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Reports from these accounts bypass K-Means++ clustering and receive an automatic confidence score of 1.0.
                    </p>
                    <div class="mt-3 flex gap-3">
                        <label class="sr-only" for="source-name">Verified source page name</label>
                        <input
                            id="source-name"
                            name="name"
                            type="text"
                            required
                            placeholder="e.g. City Government of Davao"
                            class="harvest-input min-w-0 flex-1 rounded-xl px-3 py-2.5 text-sm outline-none"
                        >
                        <input type="hidden" name="platform" value="Facebook">
                    </div>
                </form>

                <div class="harvest-row-list mt-5 space-y-2">
                    @forelse ($verifiedSources as $source)
                        <div class="harvest-row flex items-center justify-between gap-3 rounded-xl px-3.5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ $source->name }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $source->platform }} · verified {{ $source->verified_at?->format('Y-m-d') ?? $source->created_at?->format('Y-m-d') }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('harvesting.sources.destroy', $source) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="harvest-remove shrink-0 rounded-md px-2 py-1 text-sm font-medium transition">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">No whitelisted sources configured.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section id="scraped-posts" class="harvest-posts-panel mt-5 rounded-2xl p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">Scraped Facebook Flood Posts</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Filtered by <code>davao_flood_cleaner.py</code> — showing genuine Davao flood reports vs. removed noise
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <label for="per-page-select" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Show per page</label>
                    <select
                        id="per-page-select"
                        data-per-page-control
                        class="harvest-select rounded-lg px-2.5 py-2 text-xs font-bold outline-none"
                    >
                        @foreach ([5, 10, 15, 25, 50] as $amount)
                            <option value="{{ $amount }}" {{ $perPage === $amount ? 'selected' : '' }}>
                                {{ $amount }} posts
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="harvest-tabbar mt-5 flex flex-wrap gap-1.5 rounded-xl p-1.5" role="tablist">
                        @foreach ([
                            ['key' => 'actionable', 'label' => 'Verified Flood ('.number_format($actionablePostsCount ?? 0).')', 'icon' => 'alert'],
                            ['key' => 'watch', 'label' => 'No Urgency / Official Tag ('.number_format($earlyWarningPostsCount ?? 0).')', 'icon' => 'warning'],
                            ['key' => 'kept', 'label' => 'Kept / Clean ('.number_format($keptPostsCount).')', 'icon' => 'check'],
                            ['key' => 'removed', 'label' => 'Removed Noise ('.number_format($removedPostsCount).')', 'icon' => 'close'],
                            ['key' => 'all', 'label' => 'All Scraped ('.number_format($totalScrapedPosts).')', 'icon' => 'list'],
                        ] as $tab)
                            <button
                                type="button"
                                data-posts-filter="{{ $tab['key'] }}"
                                class="harvest-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold transition {{ ($filter ?? 'kept') === $tab['key'] ? 'is-selected' : '' }}"
                            >
                                @if ($tab['icon'] === 'alert')
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2.5a1 1 0 0 1 .87.5l7 12a1 1 0 0 1-.87 1.5H3a1 1 0 0 1-.87-1.5l7-12a1 1 0 0 1 .87-.5Zm0 4a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0v-3.5A.75.75 0 0 0 10 6.5Zm0 7.25a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd"/></svg>
                                @elseif ($tab['icon'] === 'warning')
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2.5a1 1 0 0 1 .87.5l7 12a1 1 0 0 1-.87 1.5H3a1 1 0 0 1-.87-1.5l7-12a1 1 0 0 1 .87-.5ZM10 7a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0v-3.5A.75.75 0 0 0 10 7Zm0 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd"/></svg>
                                @elseif ($tab['icon'] === 'check')
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 0 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                @elseif ($tab['icon'] === 'close')
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.3 4.3a1 1 0 0 1 1.4 0l4.3 4.3 4.3-4.3a1 1 0 1 1 1.4 1.4L11.4 10l4.3 4.3a1 1 0 0 1-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L8.6 10 4.3 5.7a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4.5A1.5 1.5 0 0 1 4.5 3h11A1.5 1.5 0 0 1 17 4.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 3 15.5v-11Zm3 1.5v8h1.5V6H6Zm3.25 0v8h1.5V6h-1.5Zm3.25 0v8H14V6h-1.5Z"/></svg>
                                @endif
                                {{ $tab['label'] }}
                            </button>
                        @endforeach
            </div>

            <div id="posts-dynamic-wrapper" class="mt-4">
                @include('harvesting._posts')
            </div>
        </section>
    </div>

    <script>
        (() => {
            const page = document.querySelector('[data-harvesting-page]');
            if (!page) return;

            let lastCollected = {{ (int) ($latestRun?->posts_collected ?? 0) }};
            let currentStatus = page.dataset.runStatus;

            const pollStatus = async () => {
                try {
                    const res = await fetch('{{ route('harvesting.status') }}', {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data.run) return;

                    const logEl = document.querySelector('[data-scraper-log]');
                    if (logEl && data.run.log_excerpt) {
                        logEl.textContent = data.run.log_excerpt;
                        logEl.scrollTop = logEl.scrollHeight;
                    }

                    const statusLabel = document.querySelector('[data-run-status-label]');
                    if (statusLabel) {
                        statusLabel.textContent = String(data.run.status).toUpperCase();
                    }

                    const statsEl = document.querySelector('[data-run-stats]');
                    if (statsEl) {
                        statsEl.textContent = `Collected in run: ${data.run.posts_collected} (${data.run.new_posts_saved} new)`;
                    }

                    const totalEl = document.querySelector('[data-total-scraped]');
                    if (totalEl) {
                        totalEl.textContent = `${Number(data.total_scraped_posts).toLocaleString()} posts collected`;
                    }

                    if (
                        (currentStatus === 'running' && data.run.status !== 'running') ||
                        data.run.posts_collected > lastCollected
                    ) {
                        lastCollected = data.run.posts_collected;
                        currentStatus = data.run.status;
                        window.location.reload();
                    }
                } catch (e) {
                    // ignore transient polling error
                }
            };

            if (currentStatus === 'running') {
                window.setInterval(pollStatus, 2500);
            }

            // ── AJAX Pagination, Filter Tabs & Per-Page Controls (Seamless without page reload) ──
            const wrapper = document.getElementById('posts-dynamic-wrapper');
            const perPageSelect = document.querySelector('[data-per-page-control]');
            const filterButtons = document.querySelectorAll('[data-posts-filter]');
            let activeFilter = '{{ $filter ?? 'kept' }}';

            const fetchPosts = async (url) => {
                if (!wrapper) return;
                wrapper.style.opacity = '0.5';
                wrapper.style.pointerEvents = 'none';

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) throw new Error('Failed to load posts');
                    const html = await response.text();
                    wrapper.innerHTML = html;
                } catch (err) {
                    console.error('Error fetching posts:', err);
                } finally {
                    wrapper.style.opacity = '1';
                    wrapper.style.pointerEvents = 'auto';
                }
            };

            page.addEventListener('click', (e) => {
                const pageBtn = e.target.closest('[data-posts-page-url]');
                if (pageBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetUrl = pageBtn.dataset.postsPageUrl;
                    if (targetUrl) {
                        fetchPosts(targetUrl);
                    }
                    return;
                }

                const filterBtn = e.target.closest('[data-posts-filter]');
                if (filterBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    activeFilter = filterBtn.dataset.postsFilter || 'kept';
                    filterButtons.forEach((btn) => {
                        const isSelected = btn.dataset.postsFilter === activeFilter;
                        btn.classList.toggle('is-selected', isSelected);
                    });
                    const perPage = perPageSelect ? perPageSelect.value : 5;
                    fetchPosts(`{{ route('harvesting') }}?filter=${encodeURIComponent(activeFilter)}&per_page=${encodeURIComponent(perPage)}`);
                }
            });

            if (perPageSelect) {
                perPageSelect.addEventListener('change', () => {
                    const url = `{{ route('harvesting') }}?filter=${encodeURIComponent(activeFilter)}&per_page=${encodeURIComponent(perPageSelect.value)}`;
                    fetchPosts(url);
                });
            }
        })();
    </script>
</x-layouts.app>
