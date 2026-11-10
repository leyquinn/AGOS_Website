<div data-posts-container class="space-y-3">
    @forelse ($socialPosts as $post)
        <article class="rounded-xl border {{ $post->clean_status === 'removed' ? 'border-rose-900/50 bg-[#0a1e42]/80 opacity-85' : 'border-blue-900/40 bg-[#0d2b5b]' }} p-4 transition-opacity">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-blue-200">{{ $post->author }}</span>
                    @if ($post->is_whitelisted_source)
                        <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-bold text-emerald-950">Verified Source (CS 1.0)</span>
                    @endif
                    @if ($post->search_query)
                        <span class="rounded-full bg-blue-900/80 px-2.5 py-0.5 text-[9px] font-semibold text-cyan-200">
                            Query: {{ $post->search_query }}
                        </span>
                    @endif
                    @if ($post->clean_status === 'kept')
                        <span class="rounded-full border border-emerald-500/40 bg-emerald-500/15 px-2.5 py-0.5 text-[9px] font-bold text-emerald-300">
                            ✓ Clean Flood Report
                        </span>
                    @elseif ($post->clean_status === 'removed')
                        <span class="rounded-full border border-rose-500/40 bg-rose-500/20 px-2.5 py-0.5 text-[9px] font-bold text-rose-300">
                            ✕ Noise: {{ $post->removal_reason ?: ($post->removal_category ?: 'Filtered') }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded px-2 py-0.5 text-[9px] font-bold {{ $post->urgency_level === 'SITREP' ? 'bg-rose-500 text-white' : ($post->urgency_level === 'HIGH' ? 'bg-amber-400 text-amber-950' : 'bg-slate-700 text-slate-200') }}">
                        {{ $post->urgency_level }}
                    </span>
                    <span class="text-[10px] text-slate-400">
                        {{ $post->raw_timestamp ?: ($post->scraped_at?->diffForHumans() ?? '') }}
                    </span>
                </div>
            </div>

            <p class="mt-2 whitespace-pre-line text-xs leading-relaxed text-slate-200">{{ Str::limit($post->cleaned_text ?: $post->full_text, 420) }}</p>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-400">
                <div class="flex flex-wrap gap-1.5">
                    @if (! empty($post->detected_locations))
                        @foreach ($post->detected_locations as $loc)
                            <span class="rounded bg-orange-500/20 px-2 py-0.5 text-[9px] font-bold text-orange-300">
                                📍 {{ $loc }}
                            </span>
                        @endforeach
                    @else
                        <span>No specific Davao barangay keyword matched yet</span>
                    @endif
                </div>

                @if ($post->post_url)
                    <a href="{{ $post->post_url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-cyan-300 hover:underline">
                        Open on Facebook ↗
                    </a>
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-xl border border-dashed border-blue-800/60 p-8 text-center">
            @if (($filter ?? 'kept') === 'kept' && ($removedPostsCount ?? 0) > 0)
                <p class="text-xs font-bold text-slate-300">All {{ $removedPostsCount }} scraped posts were filtered out as noise by the Flood Cleaner.</p>
                <p class="mt-1 text-[11px] text-slate-400">
                    Click the <strong>✕ Removed Noise ({{ $removedPostsCount }})</strong> tab above to inspect why each post was removed, or scrape a Davao flood query (e.g. <code>baha davao</code>).
                </p>
            @else
                <p class="text-xs font-bold text-slate-300">No posts in this view yet.</p>
                <p class="mt-1 text-[11px] text-slate-400">
                    Add a keyword listener above or type a query and click <strong>▶ Run Scraper Now</strong> to test scraping!
                </p>
            @endif
        </div>
    @endforelse
</div>

<div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-blue-900/50 pt-3 text-xs text-slate-400">
    <div>
        Showing <span class="font-bold text-slate-200">{{ $socialPosts->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-200">{{ $socialPosts->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-200">{{ $socialPosts->total() }}</span> posts
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
