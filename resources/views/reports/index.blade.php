<x-layouts.app title="Reports">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold text-slate-400">Brgy. Poblacion • Aug 23, 2026 • 10:15:00</p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">Reports #1234</h2>
                    <span class="rounded-full bg-emerald-400 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-950">● Validated</span>
                </div>
                <p class="mt-2 text-xs text-slate-400">Captured from social media feed 14 mins ago</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="rounded-xl bg-blue-200/80 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-blue-100">↗ &nbsp; Share</button>
                <button type="button" class="rounded-xl bg-blue-200/80 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-blue-100">↶ &nbsp; History</button>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-5">
                <section class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-black">● &nbsp; Original Post</h3>
                        <span class="rounded-full bg-[#d4e0f3] px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">Lang: Cebuano (Detected)</span>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed">Grabe ang baha diri sa <strong class="text-orange-500">Barangay Poblacion</strong>, hantod tuhod na sa <strong class="text-blue-700">Rizal Street</strong>. Bantay mo diha!</p>
                    <p class="mt-4 text-[10px] font-semibold text-slate-500">◉ &nbsp; @juandelacruz01 &nbsp;•&nbsp; △ &nbsp; not a verified source</p>
                </section>

                <section class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 sm:p-5">
                    <h3 class="text-xl font-black">Multi-task NLP Output</h3>
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Relevance score (P<sub>rel</sub>)</span>
                            <strong class="text-xs text-slate-600">94%</strong>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-slate-200"><div class="h-2 w-[94%] rounded-full bg-emerald-500"></div></div>
                    </div>
                    <p class="mt-5 text-sm text-slate-500">Extracted entities (NER)</p>
                    <div class="mt-4 flex flex-wrap gap-5">
                        <span class="rounded-lg bg-orange-400 px-3 py-1.5 text-xs font-bold text-white">Barangay: Poblacion</span>
                        <span class="rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-bold text-white">Street: Rizal</span>
                    </div>
                    <p class="mt-5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Model_version: MTL-XLM-R-v1.2 • Inferred 3 min ago</p>
                </section>

                <section class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 sm:p-5">
                    <h3 class="text-xl font-black">Confidence score breakdown</h3>
                    <div class="mt-5 flex flex-wrap justify-center gap-5 text-center">
                        <div class="rounded-2xl bg-[#d2dff1] px-6 py-3"><p class="text-sm text-slate-500">P<sub>rel</sub></p><strong class="text-2xl">0.94</strong></div>
                        <div class="rounded-2xl bg-[#d2dff1] px-6 py-3"><p class="text-sm text-slate-500">D<sub>clusters</sub></p><strong class="text-2xl">0.89</strong></div>
                        <div class="rounded-2xl bg-emerald-400 px-6 py-3"><p class="text-sm text-emerald-900">Final CS</p><strong class="text-2xl text-emerald-950">0.91</strong></div>
                    </div>
                    <p class="mt-5 text-[10px] font-semibold text-slate-500">CS = (0.35 × 0.94) + (0.65 × 0.89) = 0.91</p>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="overflow-hidden rounded-2xl border-2 border-blue-500 bg-[#b7c9e5]">
                    <div class="relative h-52 overflow-hidden bg-[#c9d9ed] bg-[linear-gradient(90deg,transparent_48%,rgba(71,85,105,.25)_49%,transparent_51%),linear-gradient(0deg,transparent_48%,rgba(71,85,105,.25)_49%,transparent_51%)] bg-[length:42px_34px]">
                        <div class="absolute inset-0 flex flex-wrap content-center justify-center gap-3 p-5 opacity-90">
                            @foreach (range(1, 36) as $marker)
                                <span class="h-2.5 w-2.5 rounded-full {{ $marker % 5 === 0 ? 'bg-orange-400' : ($marker % 3 === 0 ? 'bg-cyan-400' : 'bg-blue-400') }}"></span>
                            @endforeach
                        </div>
                        <span class="absolute bottom-2 left-3 text-[9px] font-semibold text-slate-500">● centroid markers, colored by CS</span>
                    </div>
                </section>

                <section class="rounded-2xl bg-[#b7c9e5] p-3 text-slate-700">
                    <div class="flex items-center justify-between px-2 pb-3">
                        <h3 class="font-bold">Cluster Consensus</h3>
                        <span class="text-[9px] text-slate-500">6 members • not an outlier</span>
                    </div>
                    <div class="space-y-2">
                        @foreach ([
                            ['text' => '"baha na sa poblacion, tabang!"', 'score' => '0.3 km'],
                            ['text' => '"rizal st. flooded, avoid nlng"', 'score' => '0.5 km'],
                            ['text' => '"grabe kadaghan sa tubig diri"', 'score' => '0.8 km'],
                            ['text' => '"nangabasa na among gamit diri"', 'score' => '0.1 km'],
                            ['text' => '"tabang, nalunod na among gamit"', 'score' => '0.4 km'],
                            ['text' => '"dili maagian diri sa may poblacion"', 'score' => '0.9 km'],
                        ] as $item)
                            <div class="flex items-center justify-between gap-2 rounded-full bg-[#9fb8dc] px-3 py-1.5 text-[9px] text-slate-600">
                                <span class="truncate">{{ $item['text'] }}</span><strong class="shrink-0 rounded-full bg-blue-600 px-2 py-0.5 text-white">{{ $item['score'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="grid grid-cols-2 gap-3">
                    <button type="button" class="rounded-xl bg-emerald-500 px-3 py-3 text-xs font-bold text-white transition hover:bg-emerald-400">✓ &nbsp; Confirm</button>
                    <button type="button" class="rounded-xl bg-red-500 px-3 py-3 text-xs font-bold text-white transition hover:bg-red-400">× &nbsp; False Alarm</button>
                </div>
                <button type="button" class="mx-auto block rounded-xl bg-orange-400 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-orange-300">⚑ &nbsp; Escalate for Review</button>
            </aside>
        </div>
    </div>
</x-layouts.app>