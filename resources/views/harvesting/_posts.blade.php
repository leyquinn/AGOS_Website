<div data-posts-container class="space-y-3">
    @forelse ($socialPosts as $post)
        <article class="harvest-post-row rounded-xl border {{ $post->clean_status === 'removed' ? 'border-rose-200 bg-rose-50/60 dark:border-rose-500/25 dark:bg-rose-500/5' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/60' }} p-4 transition-opacity">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $post->author }}</span>
                    @if ($post->is_whitelisted_source)
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Verified Source (CS 1.0)</span>
                    @endif
                    @if ($post->search_query)
                        <span class="rounded-full bg-blue-900/80 px-2.5 py-0.5 text-[9px] font-semibold text-cyan-200">
                            Query: {{ $post->search_query }}
                        </span>
                    @endif
                    @if ($post->clean_status === 'kept')
                        <span class="rounded-full border border-emerald-500/40 bg-emerald-500/15 px-2.5 py-0.5 text-[9px] font-bold text-emerald-300">
                            Cleaner: Kept
                        </span>
                    @elseif ($post->clean_status === 'removed')
                        <span class="rounded-full border border-rose-500/40 bg-rose-500/20 px-2.5 py-0.5 text-[9px] font-bold text-rose-300">
                            Cleaner: {{ $post->removal_reason ?: ($post->removal_category ?: 'Filtered') }}
                        </span>
                    @endif
                    @if ($post->analyzed_at)
                        @if ($post->model_status === 'VERIFIED_ACTIONABLE')
                            <span class="rounded-full bg-emerald-500 px-2.5 py-0.5 text-[9px] font-bold text-emerald-950">
                                VERIFIED ACTIONABLE{{ $post->discard_reason === 'verified_by_official' ? ' (OFFICIAL TAGGED)' : '' }}
                            </span>
                        @elseif ($post->model_status === 'FLOOD_MENTION_NO_URGENCY')
                            <span class="rounded-full bg-amber-400 px-2.5 py-0.5 text-[9px] font-bold text-amber-950">
                                NO URGENCY MENTIONED (AWAITING OFFICIAL TAG)
                            </span>
                        @elseif ($post->model_status === 'DISCARDED_UNLOCALIZED')
                            <span class="rounded-full border border-cyan-500/40 bg-cyan-500/15 px-2.5 py-0.5 text-[9px] font-bold text-cyan-300">
                                Unlocalized Flood
                            </span>
                        @else
                            <span class="rounded-full border border-slate-600 bg-slate-800/90 px-2.5 py-0.5 text-[9px] font-bold text-slate-300">
                                XLM-R Non-Flood{{ $post->discard_reason ? ': '.str_replace('_', ' ', $post->discard_reason) : '' }}
                            </span>
                        @endif
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($post->analyzed_at)
                        <span class="rounded bg-blue-950/90 px-2 py-0.5 font-mono text-[9px] font-bold text-cyan-300" title="XLM-RoBERTa Relevance Probability">
                            P_rel {{ number_format((float) $post->p_rel, 2) }}
                        </span>
                        <span class="rounded bg-blue-950/90 px-2 py-0.5 font-mono text-[9px] font-bold text-blue-300" title="K-Means++ Spatial Consensus">
                            D_clust {{ number_format((float) $post->d_clusters, 2) }}
                        </span>
                        <span class="rounded px-2 py-0.5 font-mono text-[9px] font-bold {{ $post->confidence_score >= 0.70 ? 'bg-emerald-500 text-emerald-950' : ($post->confidence_score >= 0.40 ? 'bg-amber-400 text-amber-950' : 'bg-slate-700 text-slate-300') }}" title="Final Confidence Score CS = 0.35*P_rel + 0.65*D_clusters">
                            CS {{ number_format((float) $post->confidence_score, 2) }}
                        </span>
                    @endif
                    <span class="rounded px-2 py-0.5 text-[9px] font-bold {{ $post->urgency_level === 'SITREP' ? 'bg-rose-500 text-white' : ($post->urgency_level === 'HIGH' ? 'bg-amber-400 text-amber-950' : 'bg-slate-700 text-slate-200') }}">
                        {{ $post->urgency_level }}
                    </span>
                    <span class="text-[10px] text-slate-400">
                        {{ $post->raw_timestamp ?: ($post->scraped_at?->diffForHumans() ?? '') }}
                    </span>
                </div>
            </div>

            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ Str::limit($post->cleaned_text ?: $post->full_text, 420) }}</p>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-400">
                <div class="flex flex-wrap items-center gap-1.5">
                    @php
                        $locs = ! empty($post->extracted_locations) ? $post->extracted_locations : ($post->detected_locations ?? []);
                    @endphp
                    @if (! empty($locs))
                        @foreach ($locs as $loc)
                            <span class="rounded bg-orange-100 px-2 py-1 text-[10px] font-bold text-orange-700 dark:bg-orange-500/15 dark:text-orange-300">
                                {{ $loc }}
                            </span>
                        @endforeach
                    @else
                        <span>No specific Davao barangay entity extracted</span>
                    @endif
                    @if ($post->river_basin)
                        <span class="rounded bg-cyan-500/15 px-2 py-0.5 text-[9px] font-semibold text-cyan-300">
                            {{ $post->river_basin }}
                        </span>
                    @endif
                    @if ($post->cluster_id !== null && $post->cluster_size > 0)
                        <span class="rounded bg-indigo-500/20 px-2 py-0.5 text-[9px] font-semibold text-indigo-300">
                            Cluster #{{ $post->cluster_id }} (n={{ $post->cluster_size }})
                        </span>
                    @endif
                </div>

                @if ($post->post_url)
                    <a href="{{ $post->post_url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-cyan-300 hover:underline">
                        Open on Facebook ↗
                    </a>
                @endif
            </div>

            @if ($post->model_status === 'FLOOD_MENTION_NO_URGENCY')
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-blue-900/50 pt-3">
                    <form method="POST" action="{{ route('harvesting.posts.verify', $post) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <label class="text-[10px] font-bold text-amber-300">Barangay Official Tagging:</label>
                        <select
                            name="barangay"
                            class="rounded-lg border border-blue-800 bg-[#061836] px-2.5 py-1.5 text-[10px] font-bold text-cyan-200 outline-none focus:border-cyan-400"
                        >
                            @php
                                $defaultBrgy = auth()->user()?->barangay?->name ?: ($post->primary_barangay ?: 'Talomo');
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

                    <form method="POST" action="{{ route('harvesting.posts.dismiss', $post) }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-lg border border-rose-500/40 bg-rose-500/15 px-2.5 py-1.5 text-[10px] font-bold text-rose-300 transition hover:bg-rose-500/30"
                        >
                            Dismiss
                        </button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <div class="rounded-xl border border-dashed border-blue-800/60 p-8 text-center">
            @if (($filter ?? 'kept') === 'kept' && ($removedPostsCount ?? 0) > 0)
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">All {{ $removedPostsCount }} scraped posts were filtered out as noise by the Flood Cleaner.</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Click the <strong>Removed Noise ({{ $removedPostsCount }})</strong> tab above to inspect why each post was removed, or scrape a Davao flood query (e.g. <code>baha davao</code>).
                </p>
            @else
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">No posts in this view yet.</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Add a keyword listener above or type a query and click <strong>Run Scraper Now</strong> to test scraping.
                </p>
            @endif
        </div>
    @endforelse
</div>

<div class="harvest-pagination mt-4 flex flex-wrap items-center justify-between gap-3 border-t pt-4 text-xs text-slate-500 dark:text-slate-400">
    <div>
        Showing <span class="font-bold text-slate-800 dark:text-slate-100">{{ $socialPosts->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-800 dark:text-slate-100">{{ $socialPosts->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-800 dark:text-slate-100">{{ $socialPosts->total() }}</span> posts
    </div>

    <div class="flex items-center gap-1.5">
        @if ($socialPosts->onFirstPage())
            <button type="button" disabled class="cursor-not-allowed rounded-lg border border-blue-900/40 bg-[#031027]/50 px-3 py-1.5 text-[11px] font-semibold text-slate-600 opacity-60">
                « Previous
            </button>
        @else
            <button type="button" data-posts-page-url="{{ $socialPosts->previousPageUrl() }}" class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-1.5 text-[11px] font-semibold text-cyan-300 transition hover:bg-blue-900/60 hover:text-white">
                « Previous
            </button>
        @endif

        <span class="px-2 text-[10px] font-semibold text-slate-500">
            Page {{ $socialPosts->currentPage() }} of {{ $socialPosts->lastPage() }}
        </span>

        @if ($socialPosts->hasMorePages())
            <button type="button" data-posts-page-url="{{ $socialPosts->nextPageUrl() }}" class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-1.5 text-[11px] font-semibold text-cyan-300 transition hover:bg-blue-900/60 hover:text-white">
                Next »
            </button>
        @else
            <button type="button" disabled class="cursor-not-allowed rounded-lg border border-blue-900/40 bg-[#031027]/50 px-3 py-1.5 text-[11px] font-semibold text-slate-600 opacity-60">
                Next »
            </button>
        @endif
    </div>
</div>
