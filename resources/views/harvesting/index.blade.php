<x-layouts.app title="Sources & Harvesting">
    <div class="w-full" data-harvesting-page data-run-status="{{ $latestRun?->status ?? 'idle' }}">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="max-w-xl text-sm leading-relaxed text-slate-400">
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

        <section class="grid gap-4 md:grid-cols-2" aria-label="Platform connections">
            <article class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-black">Facebook Scraper</h2>
                    @if ($isRunning)
                        <span data-scraper-badge class="animate-pulse rounded-full bg-amber-400 px-2.5 py-1 text-[9px] font-bold text-amber-950">Scraping Now…</span>
                    @elseif ($latestRun && $latestRun->status === 'failed')
                        <span data-scraper-badge class="rounded-full bg-rose-500 px-2.5 py-1 text-[9px] font-bold text-white">Last Run Failed</span>
                    @else
                        <span data-scraper-badge class="rounded-full bg-emerald-400 px-2.5 py-1 text-[9px] font-bold text-emerald-950">Connected</span>
                    @endif
                </div>
                <p class="mt-4 text-[10px] font-semibold text-slate-500">Scraped content collected in database</p>
                <div class="mt-2 h-1.5 rounded-full bg-slate-200">
                    <div class="h-1.5 rounded-full bg-blue-700" style="width: {{ $progressWidth }}%"></div>
                </div>
                <div class="mt-2 flex justify-between text-[9px] font-semibold text-slate-500">
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

            <article class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-black">Run Facebook Flood Scraper</h2>
                    <span class="rounded-full bg-blue-900/15 px-2.5 py-1 text-[9px] font-bold text-blue-900">{{ $activeKeywordsCount }} active {{ Str::plural('query', $activeKeywordsCount) }}</span>
                </div>

                <form method="POST" action="{{ route('harvesting.run') }}" class="mt-3 space-y-2.5">
                    @csrf
                    <div>
                        <label for="quick-query" class="block text-[10px] font-bold text-slate-600">
                            Type a specific query to scrape now (or leave blank to scrape all enabled listeners below):
                        </label>
                        <input
                            id="quick-query"
                            name="quick_query"
                            type="text"
                            placeholder="e.g. baha Matina Crossing or #bahaDavao"
                            class="mt-1 w-full rounded-xl border-0 bg-[#8fa8d0] px-3 py-2 text-xs text-slate-800 placeholder-slate-600 outline-none focus:ring-2 focus:ring-blue-600"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600">
                            <span>Scrolls:</span>
                            <select name="scrolls" class="rounded-lg border-0 bg-slate-100 px-2 py-1.5 text-[10px] font-bold text-slate-700 outline-none">
                                <option value="2">2 scrolls (Quick test)</option>
                                <option value="5" selected>5 scrolls</option>
                                <option value="15">15 scrolls</option>
                                <option value="50">50 scrolls (Deep)</option>
                            </select>
                        </label>

                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600">
                            <span>Browser:</span>
                            <select name="browser_mode" class="rounded-lg border-0 bg-slate-100 px-2 py-1.5 text-[10px] font-bold text-slate-700 outline-none">
                                <option value="visible" selected>Visible Chrome</option>
                                <option value="headless">Headless</option>
                            </select>
                        </label>

                        <button
                            type="submit"
                            class="ml-auto rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-blue-600"
                        >
                            ▶ Run Scraper Now
                        </button>
                    </div>
                </form>

                @if ($isRunning)
                    <form method="POST" action="{{ route('harvesting.stop') }}" class="mt-2 flex justify-end">
                        @csrf
                        <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-rose-500">
                            ■ Stop Active Run
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
        <div class="mt-5 rounded-2xl border border-blue-900/40 bg-[#071738]/90 p-3.5 shadow-lg">
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs font-bold text-slate-300">
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wider text-blue-400">Sequential Pipeline:</span>
                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black {{ ($pendingCleanCount ?? 0) > 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : (($pendingModelCount ?? 0) > 0 ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40' : 'bg-slate-700/40 text-slate-300 border border-slate-600/40') }}">
                        @if (($pendingCleanCount ?? 0) > 0)
                            Stage 2 Active: {{ $pendingCleanCount }} Scraped Posts Awaiting Flood Cleaner
                        @elseif (($pendingModelCount ?? 0) > 0)
                            Stage 3 Active: {{ $pendingModelCount }} Clean Posts Awaiting XLM-RoBERTa Model
                        @else
                            All Posts Up to Date (Run Step 1 Scraper to harvest new flood posts)
                        @endif
                    </span>
                </div>
                <div class="flex items-center gap-2 text-[10px]">
                    <span class="flex items-center gap-1 rounded-lg px-2.5 py-1 {{ $isRunning ? 'bg-blue-600/40 text-blue-200 border border-blue-500/50 animate-pulse' : 'bg-slate-800 text-slate-300' }}">
                        <span>Step 1: Scrape</span>
                    </span>
                    <span class="text-slate-500">➔</span>
                    <span class="flex items-center gap-1 rounded-lg px-2.5 py-1 {{ ($canRunCleaner ?? false) ? 'bg-emerald-600/40 text-emerald-200 border border-emerald-500/50 font-black' : 'bg-slate-800/60 text-slate-500' }}">
                        <span>Step 2: Clean {{ ($pendingCleanCount ?? 0) > 0 ? '('.$pendingCleanCount.')' : '' }}</span>
                    </span>
                    <span class="text-slate-500">➔</span>
                    <span class="flex items-center gap-1 rounded-lg px-2.5 py-1 {{ ($canRunModel ?? false) ? 'bg-blue-600/40 text-blue-200 border border-blue-500/50 font-black' : 'bg-slate-800/60 text-slate-500' }}">
                        <span>Step 3: Model {{ ($pendingModelCount ?? 0) > 0 ? '('.$pendingModelCount.')' : '' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <section class="portal-card mt-5 rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black">Pipeline Queue &amp; Flood Cleaner</h2>
                    <p class="mt-0.5 text-[10px] font-semibold text-slate-500">
                        Multi-layer rule filter: strips Facebook UI artifacts, removes real-estate/business ads, non-Davao floods, negated phrases, and near-duplicates.
                    </p>
                </div>

                <form method="POST" action="{{ route('harvesting.clean') }}" class="flex items-center gap-2.5">
                    @csrf
                    <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600">
                        <input type="checkbox" name="strict" value="1" class="h-3.5 w-3.5 rounded border-slate-400" {{ !($canRunCleaner ?? false) ? 'disabled' : '' }}>
                        <span>Strict filter</span>
                    </label>
                    @if ($canRunCleaner ?? false)
                        <button type="submit" class="flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow transition hover:bg-emerald-500">
                            <span>🧹 Run Flood Cleaner Now</span>
                            <span class="rounded-full bg-emerald-800/80 px-2 py-0.5 text-[10px] font-black text-emerald-200">{{ $pendingCleanCount }} new</span>
                        </button>
                    @else
                        <button type="button" disabled class="cursor-not-allowed rounded-xl bg-slate-400/80 px-3.5 py-2 text-xs font-bold text-slate-200 opacity-60 shadow" title="Cleaner locked. Run the Facebook Scraper first to collect new posts.">
                            🔒 Cleaner Locked (No new posts)
                        </button>
                    @endif
                </form>
            </div>

            @php
                $retentionRate = $totalScrapedPosts > 0 ? round(($keptPostsCount / $totalScrapedPosts) * 100, 1) : 0;
            @endphp

            <div class="mt-6 grid gap-5 text-center sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Scraped Input', 'value' => number_format($totalScrapedPosts).' posts', 'class' => 'text-blue-700 dark:text-blue-400'],
                    ['label' => 'Kept (Clean Flood Posts)', 'value' => number_format($keptPostsCount).' kept', 'class' => 'text-emerald-500'],
                    ['label' => 'Removed Noise / Ads / Dups', 'value' => number_format($removedPostsCount).' removed', 'class' => 'text-rose-400'],
                    ['label' => 'Cleaner Retention Rate', 'value' => $retentionRate.'%', 'class' => 'text-cyan-300'],
                ] as $metric)
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-xl font-black {{ $metric['class'] }}">{{ $metric['value'] }}</p>
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

        <section class="portal-card mt-5 rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-lg font-black">Multi-Task XLM-RoBERTa + K-Means++ Intelligence</h2>
                        @if ($checkpointReady ?? false)
                            <span class="rounded-full border border-emerald-500/40 bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-400">
                                ● Checkpoint Ready (best_model.pt)
                            </span>
                        @else
                            <span class="rounded-full border border-amber-500/40 bg-amber-500/15 px-2.5 py-0.5 text-[10px] font-bold text-amber-300">
                                ⚠ Checkpoint Missing in intelligence/model/checkpoints/
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-[10px] font-semibold text-slate-500">
                        Shared <code>xlm-roberta-base</code> encoder with Task Head A (Relevance Classification <em>P<sub>rel</sub></em>), Task Head B (Barangay IOB2 NER + Gazetteer), and K-Means++ Spatial Consensus (<em>CS = 0.35·P<sub>rel</sub> + 0.65·D<sub>clusters</sub></em>).
                    </p>
                </div>

                <form method="POST" action="{{ route('harvesting.analyze') }}" class="flex flex-wrap items-center gap-2.5">
                    @csrf
                    <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600">
                        <span>Scope:</span>
                        <select name="scope" class="rounded-lg border-0 bg-slate-100 px-2.5 py-1.5 text-[10px] font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500" {{ !($canRunModel ?? false) ? 'disabled' : '' }}>
                            <option value="pending" selected>Pending Clean Posts ({{ $pendingModelCount ?? 0 }})</option>
                            <option value="kept">All Kept / Clean posts ({{ $keptPostsCount }})</option>
                            <option value="all">All scraped posts ({{ $totalScrapedPosts }})</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600">
                        <span>Threshold:</span>
                        <select name="threshold" class="rounded-lg border-0 bg-slate-100 px-2 py-1.5 text-[10px] font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500" {{ !($canRunModel ?? false) ? 'disabled' : '' }}>
                            <option value="0.50">P_rel ≥ 0.50</option>
                            <option value="0.60">P_rel ≥ 0.60</option>
                            <option value="0.70">P_rel ≥ 0.70</option>
                        </select>
                    </label>
                    @if ($canRunModel ?? false)
                        <button type="submit" class="flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow transition hover:bg-blue-500">
                            <span>🧠 Run XLM-RoBERTa Model Now</span>
                            <span class="rounded-full bg-blue-800/80 px-2 py-0.5 text-[10px] font-black text-blue-200">{{ $pendingModelCount }} clean</span>
                        </button>
                    @else
                        <button type="button" disabled class="cursor-not-allowed rounded-xl bg-slate-400/80 px-3.5 py-2 text-xs font-bold text-slate-200 opacity-60 shadow" title="{{ ($pendingCleanCount ?? 0) > 0 ? 'Model locked. Run Flood Cleaner first.' : 'Model locked. All posts analyzed.' }}">
                            🔒 Model Locked ({{ ($pendingCleanCount ?? 0) > 0 ? 'Clean '.$pendingCleanCount.' pending posts first' : 'All posts analyzed' }})
                        </button>
                    @endif
                </form>
            </div>

            <div class="mt-6 grid gap-5 text-center sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Posts Analyzed by XLM-R', 'value' => number_format($analyzedPostsCount ?? 0).' / '.number_format($totalScrapedPosts), 'class' => 'text-blue-700 dark:text-blue-400'],
                    ['label' => 'Verified Actionable (Emergency)', 'value' => number_format($actionablePostsCount ?? 0).' actionable', 'class' => 'text-emerald-500'],
                    ['label' => 'Flood Watch / HITL (No Urgency)', 'value' => number_format($earlyWarningPostsCount ?? 0).' watch', 'class' => 'text-amber-400'],
                    ['label' => 'Avg. Confidence Score (CS)', 'value' => number_format($avgConfidenceScore ?? 0, 2), 'class' => 'text-cyan-300'],
                ] as $metric)
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-xl font-black {{ $metric['class'] }}">{{ $metric['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mt-5 grid gap-5 xl:grid-cols-2">
            <section class="portal-card rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10 sm:p-5">
                <form method="POST" action="{{ route('harvesting.keywords.store') }}">
                    @csrf
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black">Keyword &amp; hashtag listeners</h2>
                        <button type="submit" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-white">＋ Add</button>
                    </div>
                    <div class="mt-5 flex gap-3">
                        <label class="sr-only" for="listener-keyword">Add a keyword or hashtag</label>
                        <input
                            id="listener-keyword"
                            name="keyword"
                            type="text"
                            required
                            placeholder="e.g. #bahaDavao or baha Matina Pangi"
                            class="min-w-0 flex-1 rounded-xl border-0 bg-[#8fa8d0] px-3 py-2.5 text-xs text-slate-800 placeholder-slate-500 outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <label class="sr-only" for="listener-platform">Listener platform</label>
                        <select id="listener-platform" name="platform" class="w-24 rounded-xl border-0 bg-slate-100 px-2 py-2.5 text-[10px] font-semibold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500 sm:w-28">
                            <option value="Facebook">Facebook</option>
                        </select>
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($keywords as $listener)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-[#91acd5] px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-700 {{ $listener->is_active ? '' : 'line-through opacity-60' }}">
                                    {{ $listener->keyword }}
                                </p>
                                <p class="mt-0.5 text-[9px] text-slate-600">
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
                                        class="relative h-5 w-9 shrink-0 rounded-full transition {{ $listener->is_active ? 'bg-slate-950' : 'bg-slate-400' }}"
                                        title="{{ $listener->is_active ? 'Pause listener' : 'Enable listener' }}"
                                        aria-label="{{ $listener->is_active ? 'Disable' : 'Enable' }} {{ $listener->keyword }}"
                                    >
                                        <span class="absolute top-1 h-3 w-3 rounded-full bg-cyan-300 transition-all {{ $listener->is_active ? 'right-1' : 'left-1' }}"></span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('harvesting.keywords.destroy', $listener) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[10px] font-bold text-red-600 transition hover:text-red-800" title="Remove keyword">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-600">No keyword listeners added yet. Add one above to start scraping.</p>
                    @endforelse
                </div>
            </section>

            <section class="portal-card rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10 sm:p-5">
                <form method="POST" action="{{ route('harvesting.sources.store') }}">
                    @csrf
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black">Verified source whitelist</h2>
                        <button type="submit" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-white">＋ Add</button>
                    </div>
                    <p class="mt-3 text-[10px] leading-relaxed text-slate-500">
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
                            class="min-w-0 flex-1 rounded-xl border-0 bg-[#8fa8d0] px-3 py-2.5 text-xs text-slate-800 placeholder-slate-500 outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <input type="hidden" name="platform" value="Facebook">
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($verifiedSources as $source)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-[#91acd5] px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-700">{{ $source->name }}</p>
                                <p class="mt-0.5 text-[9px] text-slate-600">
                                    {{ $source->platform }} · verified {{ $source->verified_at?->format('Y-m-d') ?? $source->created_at?->format('Y-m-d') }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('harvesting.sources.destroy', $source) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="shrink-0 text-[10px] font-bold text-red-600 transition hover:text-red-800">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-slate-600">No whitelisted sources configured.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section id="scraped-posts" class="mt-5 rounded-2xl border border-blue-900/50 bg-[#071738]/80 p-5 shadow-lg shadow-blue-950/10">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Scraped Facebook Flood Posts</h2>
                    <p class="mt-1 text-[10px] text-slate-400">
                        Filtered by <code>davao_flood_cleaner.py</code> — showing genuine Davao flood reports vs. removed noise
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex flex-wrap rounded-xl border border-blue-900/60 bg-[#031027] p-1" role="tablist">
                        @foreach ([
                            ['key' => 'actionable', 'label' => '🚨 Verified Flood ('.number_format($actionablePostsCount ?? 0).')'],
                            ['key' => 'watch', 'label' => '⚠ No Urgency / Official Tag ('.number_format($earlyWarningPostsCount ?? 0).')'],
                            ['key' => 'kept', 'label' => '✓ Kept / Clean ('.number_format($keptPostsCount).')'],
                            ['key' => 'removed', 'label' => '✕ Removed Noise ('.number_format($removedPostsCount).')'],
                            ['key' => 'all', 'label' => 'All Scraped ('.number_format($totalScrapedPosts).')'],
                        ] as $tab)
                            <button
                                type="button"
                                data-posts-filter="{{ $tab['key'] }}"
                                class="rounded-lg px-3 py-1.5 text-[10px] font-bold transition {{ ($filter ?? 'kept') === $tab['key'] ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white' }}"
                            >
                                {{ $tab['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="per-page-select" class="text-[10px] font-semibold text-slate-400">Show per page:</label>
                        <select
                            id="per-page-select"
                            data-per-page-control
                            class="rounded-lg border border-blue-900/60 bg-[#031027] px-2.5 py-1.5 text-[10px] font-bold text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400"
                        >
                            @foreach ([5, 10, 15, 25, 50] as $amount)
                                <option value="{{ $amount }}" {{ $perPage === $amount ? 'selected' : '' }}>
                                    {{ $amount }} posts
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
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
                        btn.classList.toggle('bg-blue-600', isSelected);
                        btn.classList.toggle('text-white', isSelected);
                        btn.classList.toggle('text-slate-400', !isSelected);
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
