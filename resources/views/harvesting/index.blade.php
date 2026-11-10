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

        @if ($errors->any())
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

        <section class="portal-card mt-5 rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
            <h2 class="text-lg font-black">Pipeline Queue &amp; Health</h2>
            <div class="mt-6 grid gap-5 text-center sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Harvest Interval', 'value' => '1 hour', 'class' => 'text-blue-700 dark:text-blue-400', 'key' => 'harvest'],
                    ['label' => 'Active Listeners', 'value' => $activeKeywordsCount.' active', 'class' => 'text-blue-700 dark:text-blue-400'],
                    ['label' => 'Scraped Posts Stored', 'value' => number_format($totalScrapedPosts).' posts', 'class' => 'text-emerald-600'],
                    ['label' => 'Whitelisted Sources', 'value' => $verifiedSources->count().' verified', 'class' => 'text-orange-500'],
                ] as $metric)
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-xl font-black {{ $metric['class'] }}" @isset($metric['key']) data-harvest-interval-display @endisset>{{ $metric['value'] }}</p>
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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Scraped Facebook Flood Posts</h2>
                    <p class="mt-1 text-[10px] text-slate-400">
                        Live feed of posts harvested by the scraper using your configured search queries (<span data-posts-total-count>{{ number_format($totalScrapedPosts) }}</span> total)
                    </p>
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

            // ── AJAX Pagination & Per-Page Controls (Seamless without page reload) ──
            const wrapper = document.getElementById('posts-dynamic-wrapper');
            const perPageSelect = document.querySelector('[data-per-page-control]');

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

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-posts-page-url]');
                if (!btn) return;
                e.preventDefault();
                const targetUrl = btn.dataset.postsPageUrl;
                if (targetUrl) {
                    fetchPosts(targetUrl);
                }
            });

            if (perPageSelect) {
                perPageSelect.addEventListener('change', () => {
                    const url = `{{ route('harvesting') }}?per_page=${perPageSelect.value}`;
                    fetchPosts(url);
                });
            }
        })();
    </script>
</x-layouts.app>
