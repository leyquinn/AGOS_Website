<x-layouts.app title="Sources & Harvesting">
    <div class="w-full">
        <div class="mb-8">
            <p class="max-w-xl text-sm leading-relaxed text-slate-400">
                Manage platform connections, keyword listeners, and the source authority whitelist.
            </p>
        </div>

        <section class="grid gap-4 md:grid-cols-2" aria-label="Platform connections">
            @foreach ([
                ['name' => 'Facebook', 'status' => 'Connected', 'usage' => '620 posts collected', 'refresh' => 'last scrape 14 min ago', 'width' => '62%', 'statusClass' => 'bg-emerald-400 text-emerald-950'],
            ] as $platform)
                <article class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-black">{{ $platform['name'] }}</h2>
                        <span class="{{ $platform['statusClass'] }} rounded-full px-2.5 py-1 text-[9px] font-bold">{{ $platform['status'] }}</span>
                    </div>
                    <p class="mt-4 text-[10px] font-semibold text-slate-500">Scraped content collected</p>
                    <div class="mt-2 h-1.5 rounded-full bg-slate-200">
                        <div class="h-1.5 rounded-full {{ $platform['alert'] ?? false ? 'bg-red-500' : 'bg-blue-700' }}" style="width: {{ $platform['width'] }}"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-[9px] font-semibold {{ $platform['alert'] ?? false ? 'text-red-500' : 'text-slate-500' }}">
                        <span>{{ $platform['usage'] }}</span>
                        <span>{{ $platform['refresh'] }}</span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="portal-card mt-5 rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
            <h2 class="text-lg font-black">Pipeline Queue &amp; Health</h2>
            <div class="mt-6 grid gap-5 text-center sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Harvest Interval', 'value' => '1 hour', 'class' => 'text-blue-700 dark:text-blue-400', 'key' => 'harvest'],
                    ['label' => 'Clustering Pass Interval', 'value' => '1 hour', 'class' => 'text-blue-700 dark:text-blue-400'],
                    ['label' => 'Preprocessing Queue', 'value' => '12 pending', 'class' => 'text-emerald-500'],
                    ['label' => 'ML Analysis Queue', 'value' => '47 pending', 'class' => 'text-orange-500'],
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
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Keyword &amp; hashtag listeners</h2>
                    <button type="button" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-white">＋ Add</button>
                </div>
                <div class="mt-5 flex gap-3">
                    <label class="sr-only" for="listener-keyword">Add a keyword or hashtag</label>
                    <input id="listener-keyword" type="text" placeholder="e.g #bahaDavao" class="min-w-0 flex-1 rounded-xl border-0 bg-[#8fa8d0] px-3 py-2.5 text-xs text-slate-800 placeholder-slate-500 outline-none focus:ring-2 focus:ring-blue-500">
                    <label class="sr-only" for="listener-platform">Listener platform</label>
                    <select id="listener-platform" class="w-24 rounded-xl border-0 bg-slate-100 px-2 py-2.5 text-[10px] font-semibold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500 sm:w-28">
                        <option>Facebook</option>
                    </select>
                </div>
                <div class="mt-5 space-y-3">
                    @foreach ([
                        ['keyword' => '#bahaDavao', 'platform' => 'Facebook', 'count' => '88 matches today'],
                        ['keyword' => 'grabe baha', 'platform' => 'Facebook', 'count' => '88 matches today'],
                        ['keyword' => 'tulo ang tubig', 'platform' => 'Facebook', 'count' => '12 matches today'],
                    ] as $listener)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-[#91acd5] px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-700">{{ $listener['keyword'] }}</p>
                                <p class="mt-0.5 text-[9px] text-slate-500">{{ $listener['platform'] }} · {{ $listener['count'] }}</p>
                            </div>
                            <button type="button" class="relative h-5 w-9 shrink-0 rounded-full bg-slate-950" aria-label="Disable {{ $listener['keyword'] }}">
                                <span class="absolute right-1 top-1 h-3 w-3 rounded-full bg-cyan-300"></span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="portal-card rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Verified source whitelist</h2>
                    <button type="button" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-white">＋ Add</button>
                </div>
                <p class="mt-5 text-[10px] leading-relaxed text-slate-500">
                    Reports from these accounts bypass K-Means++ clustering and receive an automatic confidence score of 1.0.
                </p>
                <div class="mt-5 space-y-3">
                    @foreach ([
                        ['name' => 'Davao City DRRMO Page', 'platform' => 'Facebook', 'date' => 'verified 2025-11-02'],
                    ] as $source)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-[#91acd5] px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-700">{{ $source['name'] }}</p>
                                <p class="mt-0.5 text-[9px] text-slate-500">{{ $source['platform'] }} · {{ $source['date'] }}</p>
                            </div>
                            <button type="button" class="shrink-0 text-[10px] font-bold text-red-500 transition hover:text-red-700">Remove</button>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>