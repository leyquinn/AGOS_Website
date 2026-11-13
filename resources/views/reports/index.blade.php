<x-layouts.app title="Reports">
    <div class="reports-page w-full">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400">Citizen-submitted flood reports • {{ $totalCount }} total ({{ $pendingCount }} pending)</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">Community reports</h2>
                <p class="mt-2 text-xs text-slate-400">New Citizen App submissions appear below for manual review. Tagged reports are removed from the feed while keeping the Submission policy totals updated.</p>
            </div>
            <span class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-[10px] font-bold text-emerald-300">Citizen App source only</span>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-xs font-bold text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <section class="report-card mb-6 overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-blue-900/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white">Citizen report flood heatmap</h2>
                    <p data-map-status class="mt-1 text-[10px] text-slate-400">Loading Davao City barangay boundaries...</p>
                </div>
                <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-400">
                    <span class="sr-only">Filter heatmap by barangay</span>
                    <select data-barangay-filter class="rounded-lg border border-blue-900/60 bg-[#031027] px-3 py-2 text-[10px] text-slate-200 outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                        <option value="">All barangays</option>
                    </select>
                </label>
            </div>
            <div class="relative h-[26rem] bg-[#0b2852]">
                <div data-flood-map data-boundary-url="{{ asset('data/davao-barangays.geojson') }}" data-heat-points='@json($mapPoints)' class="h-full w-full"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 right-0 bg-[#071738]/85 px-5 py-3 text-xs text-slate-400">Heat intensity is clipped to the selected barangay boundary</div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-4">
                @forelse ($pendingReports as $report)
                    <article class="report-card report-review-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="report-location-badge inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide">
                                        <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10.5c0 5-8 10-8 10s-8-5-8-10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10.5" r="2.5"/></svg>
                                        {{ $report->barangay?->name ?? 'Location pending' }}
                                    </span>
                                    <span class="tabular-nums text-xs font-medium text-slate-500 dark:text-slate-400">{{ $report->created_at->format('M d, Y • H:i') }}</span>
                                </div>
                            </div>
                            <span class="report-status-badge report-status-pending rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide">Pending Review</span>
                        </div>

                        <div class="report-message mt-4 rounded-xl px-4 py-3.5">
                            <p class="text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $report->report_text }}</p>
                        </div>

                        <div class="mt-4 flex flex-col gap-4 border-t border-slate-200 pt-4 dark:border-blue-900/40 lg:flex-row lg:items-end lg:justify-between">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                                    <span>Submitted by {{ $report->user ? trim($report->user->first_name.' '.$report->user->last_name) : 'Citizen user' }}</span>
                                </span>
                                <span class="report-attribute-pill inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1">
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M6 8h8.5a3 3 0 1 1 0 6H6m0 0h8.5a3 3 0 1 1 0 6H6"/></svg>
                                    <span>Water level: <strong>{{ str_replace('_', ' ', $report->water_level_status) }}</strong></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg>
                                    <span class="tabular-nums">{{ $report->created_at->diffForHumans() }}</span>
                                </span>
                            </div>

                            <form method="POST" action="{{ route('reports.tag', $report) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <button
                                    type="submit"
                                    name="tag"
                                    value="yes"
                                    class="report-action report-action-yes inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold transition hover:-translate-y-0.5"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 0 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                    Yes (Flood Report)
                                </button>
                                <button
                                    type="submit"
                                    name="tag"
                                    value="no"
                                    class="report-action report-action-no inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold transition hover:-translate-y-0.5"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.3 4.3a1 1 0 0 1 1.4 0l4.3 4.3 4.3-4.3a1 1 0 1 1 1.4 1.4L11.4 10l4.3 4.3a1 1 0 0 1-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L8.6 10 4.3 5.7a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
                                    No (Not Flood)
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="report-card report-empty-state border-dashed p-10 text-center">
                        <svg aria-hidden="true" class="mx-auto h-10 w-10 text-slate-400 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h2l1.2-2h4.6l1.2 2h2A2.5 2.5 0 0 1 20 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-9Z"/><path d="m8 13 2.2 2.2L16 9.5"/></svg>
                        <p class="text-sm font-bold text-white">No new reports awaiting tagging</p>
                        <p class="mt-2 text-xs text-slate-400">All current submissions have been reviewed. New reports submitted from the Citizen App will appear here.</p>
                    </div>
                @endforelse
            </div>

            <aside class="space-y-6">
                <section class="report-card report-policy-card p-5">
                    <div class="flex items-start gap-3">
                        <span class="report-policy-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-xl">
                            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <div>
                            <h2 class="text-base font-black text-white">Submission policy</h2>
                            <p class="mt-2 text-xs leading-relaxed text-slate-400">The admin reports feed accepts data only from users authenticated through the AGOS Citizen App. Every submission undergoes manual review by an administrator or barangay official.</p>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        <div class="report-metric-row"><span><i class="report-metric-dot bg-slate-400"></i>Total submissions</span><strong class="tabular-nums text-white">{{ $totalCount }}</strong></div>
                        <div class="report-metric-row"><span><i class="report-metric-dot bg-amber-400"></i>Pending review</span><strong class="tabular-nums text-amber-300">{{ $pendingCount }}</strong></div>
                        <div class="report-metric-row"><span><i class="report-metric-dot bg-emerald-400"></i>Yes (Flood report)</span><strong class="tabular-nums text-emerald-300">{{ $verifiedCount }}</strong></div>
                        <div class="report-metric-row"><span><i class="report-metric-dot bg-rose-400"></i>No (Not flood)</span><strong class="tabular-nums text-rose-300">{{ $rejectedCount }}</strong></div>
                    </div>
                </section>
            </aside>
        </section>
    </div>
</x-layouts.app>
