<x-layouts.app title="Reports">
    <div class="reports-page w-full">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400">Citizen-submitted flood reports • {{ $reports->count() }} total</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">Community reports</h2>
                <p class="mt-2 text-xs text-slate-400">Only authenticated Citizen App submissions are shown here. Officials manually tag each submission as a flood report or not flood.</p>
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
                @forelse ($reports as $report)
                    @php
                        $statusBadge = match ($report->verification_status) {
                            'verified' => ['label' => 'Yes · Flood Report', 'class' => 'bg-emerald-500 text-emerald-950'],
                            'rejected' => ['label' => 'No · Not Flood', 'class' => 'bg-rose-500 text-white'],
                            default => ['label' => 'Pending Review', 'class' => 'bg-amber-400 text-amber-950'],
                        };
                    @endphp
                    <article class="report-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-blue-300">{{ $report->barangay?->name ?? 'Location pending' }} • {{ $report->created_at->format('M d, Y • H:i') }}</p>
                                <h3 class="mt-1 text-sm font-bold text-white">{{ $report->report_text }}</h3>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[9px] font-bold uppercase {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-blue-900/40 pt-4">
                            <div class="flex flex-wrap gap-x-5 gap-y-2 text-[10px] text-slate-400">
                                <span>Submitted by {{ $report->user ? trim($report->user->first_name.' '.$report->user->last_name) : 'Citizen user' }}</span>
                                <span>Water level: <strong class="text-slate-200">{{ str_replace('_', ' ', $report->water_level_status) }}</strong></span>
                                <span>{{ $report->created_at->diffForHumans() }}</span>
                            </div>

                            <form method="POST" action="{{ route('reports.tag', $report) }}" class="flex items-center gap-2">
                                @csrf
                                <button
                                    type="submit"
                                    name="tag"
                                    value="yes"
                                    class="rounded-lg px-3 py-1.5 text-[10px] font-bold transition {{ $report->verification_status === 'verified' ? 'bg-emerald-500 text-emerald-950 ring-2 ring-emerald-300' : 'border border-emerald-500/40 bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25' }}"
                                >
                                    Yes (Flood Report)
                                </button>
                                <button
                                    type="submit"
                                    name="tag"
                                    value="no"
                                    class="rounded-lg px-3 py-1.5 text-[10px] font-bold transition {{ $report->verification_status === 'rejected' ? 'bg-rose-500 text-white ring-2 ring-rose-300' : 'border border-rose-500/40 bg-rose-500/15 text-rose-300 hover:bg-rose-500/25' }}"
                                >
                                    No (Not Flood)
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="report-card border-dashed p-10 text-center">
                        <p class="text-sm font-bold text-white">No Citizen App reports yet</p>
                        <p class="mt-2 text-xs text-slate-400">Reports submitted by authenticated citizen accounts will appear here.</p>
                    </div>
                @endforelse
            </div>

            <aside class="space-y-6">
                <section class="report-card p-5">
                    <h2 class="text-sm font-bold text-white">Submission policy</h2>
                    <p class="mt-3 text-xs leading-relaxed text-slate-400">The admin reports feed accepts data only from users authenticated through the AGOS Citizen App. Every submission undergoes manual review by an administrator or barangay official.</p>
                    <div class="mt-5 space-y-3 text-[10px]">
                        <div class="flex justify-between border-b border-blue-900/40 pb-3"><span class="text-slate-400">Total submissions</span><strong class="text-white">{{ $reports->count() }}</strong></div>
                        <div class="flex justify-between border-b border-blue-900/40 pb-3"><span class="text-slate-400">Pending review</span><strong class="text-amber-300">{{ $reports->where('verification_status', 'pending')->count() }}</strong></div>
                        <div class="flex justify-between border-b border-blue-900/40 pb-3"><span class="text-slate-400">Yes (Flood report)</span><strong class="text-emerald-300">{{ $reports->where('verification_status', 'verified')->count() }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-400">No (Not flood)</span><strong class="text-rose-300">{{ $reports->where('verification_status', 'rejected')->count() }}</strong></div>
                    </div>
                </section>
            </aside>
        </section>
    </div>
</x-layouts.app>
