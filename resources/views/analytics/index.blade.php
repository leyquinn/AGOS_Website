<x-layouts.app title="Model Analytics">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <p class="max-w-md text-xs leading-relaxed text-slate-400">
                Evaluation metrics for the thesis defense baseline vs. integrated pipeline.
            </p>
            <select class="rounded-full border-0 bg-[#b7c9e5] px-4 py-2 text-[10px] font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500" aria-label="Select model version">
                <option>mtl-xlmr-v1.2 (current)</option>
                <option>mtl-xlmr-v1.1</option>
                <option>v1.0 (baseline)</option>
            </select>
        </div>

        <section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Model summary metrics">
            @foreach ([
                ['label' => 'Accuracy', 'value' => '142', 'class' => 'text-slate-500'],
                ['label' => 'Precision', 'value' => '18', 'class' => 'text-slate-500'],
                ['label' => 'Recall', 'value' => '9', 'class' => 'text-slate-500'],
                ['label' => 'F1-Score', 'value' => '0.81', 'class' => 'text-blue-700'],
            ] as $metric)
                <article class="rounded-2xl bg-[#b7c9e5] px-4 py-3 text-center shadow-lg shadow-blue-950/10">
                    <p class="text-xs font-bold text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-black {{ $metric['class'] }}">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-[1.1fr_1fr]">
            <article class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                <h2 class="text-xs font-black">Per Barangay Performance</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[26rem] text-center text-[9px]">
                        <thead class="text-slate-500">
                            <tr><th class="pb-3 text-left font-semibold">Barangay</th><th class="pb-3 font-semibold">Precision</th><th class="pb-3 font-semibold">Recall</th><th class="pb-3 font-semibold">F1</th></tr>
                        </thead>
                        <tbody class="text-slate-500">
                            @foreach ([['Poblacion', '0.94', '0.91', '0.92'], ['Matina', '0.87', '0.83', '0.85'], ['Talomo', '0.96', '0.95', '0.95'], ['Bunawan', '0.79', '0.74', '0.76']] as $row)
                                <tr><td class="py-1.5 text-left">{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td><td>{{ $row[3] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                <h2 class="text-xs font-black">Confusion Matrix (valid set)</h2>
                <div class="mt-5 grid grid-cols-[5rem_1fr_1fr] items-center gap-2 text-center text-[9px] text-slate-500">
                    <span></span><span>Prediction flood</span><span>Prediction non-flood</span>
                    <span class="text-left">Actual flood</span><strong class="rounded-lg bg-emerald-500 px-3 py-2 text-white">412</strong><strong class="rounded-lg bg-red-500 px-3 py-2 text-white">38</strong>
                    <span class="text-left">Actual non-flood</span><strong class="rounded-lg bg-red-500 px-3 py-2 text-white">29</strong><strong class="rounded-lg bg-emerald-500 px-3 py-2 text-white">551</strong>
                </div>
            </article>
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-2">
            <article class="overflow-hidden rounded-2xl bg-[#b7c9e5] text-slate-700 shadow-lg shadow-blue-950/10">
                <div class="p-4">
                    <h2 class="text-sm font-black">Baseline vs. Integrated Pipeline</h2>
                    <p class="text-[10px] text-slate-500">Classification-only (w2 = 0) vs. multi-task + K-Means++ consensus</p>
                </div>
                <div class="relative h-52 border-t border-blue-200/70 px-5 pb-8 pt-5">
                    <div class="absolute inset-x-5 top-5 bottom-8 flex items-end justify-around border-b border-slate-300/70 bg-[linear-gradient(to_top,rgba(255,255,255,.18)_1px,transparent_1px)] bg-[length:100%_25%]">
                        @foreach ([['0.82', '0.94'], ['0.78', '0.91'], ['0.75', '0.89'], ['0.77', '0.90']] as $bars)
                            <div class="flex h-full items-end gap-1.5">
                                <span class="w-7 rounded-t bg-[#9bb5e1]" style="height: {{ $bars[0] * 100 }}%"></span>
                                <span class="w-7 rounded-t bg-blue-800" style="height: {{ $bars[1] * 100 }}%"></span>
                            </div>
                        @endforeach
                    </div>
                    <div class="absolute inset-x-5 bottom-2 flex justify-around text-[9px] text-slate-500"><span>Accuracy</span><span>Precision</span><span>Recall</span><span>F1-score</span></div>
                </div>
                <div class="flex gap-5 border-t border-blue-200/70 px-5 py-3 text-[9px] text-slate-500"><span><i class="mr-1 inline-block h-2 w-2 bg-[#9bb5e1]"></i>Baseline (classification only)</span><span><i class="mr-1 inline-block h-2 w-2 bg-blue-800"></i>Integrated (MTL + K-Means++)</span></div>
            </article>

            <article class="overflow-hidden rounded-2xl bg-[#b7c9e5] text-slate-700 shadow-lg shadow-blue-950/10">
                <div class="p-4">
                    <h2 class="text-sm font-black">F1 Score Across Model Versions</h2>
                    <p class="text-[10px] text-slate-500">Improvement from active-learning retraining on confirmed/false-alarm feedback</p>
                </div>
                <div class="relative h-60 border-t border-blue-200/70 px-5 pb-8 pt-5">
                    <div class="absolute inset-x-7 top-8 bottom-9 border-b border-slate-300/70 bg-[linear-gradient(to_top,rgba(255,255,255,.18)_1px,transparent_1px)] bg-[length:100%_25%]">
                        <svg class="h-full w-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="F1 score trend">
                            <polyline points="0,62 50,38 100,28" fill="none" stroke="#06499f" stroke-width="2" vector-effect="non-scaling-stroke"></polyline>
                            <circle cx="0" cy="62" r="2" fill="#06499f"></circle><circle cx="50" cy="38" r="2" fill="#06499f"></circle><circle cx="100" cy="28" r="2" fill="#06499f"></circle>
                        </svg>
                    </div>
                    <div class="absolute inset-x-5 bottom-2 flex justify-between text-[9px] text-slate-500"><span>v1.0 (baseline)</span><span>v1.1</span><span>v1.2 (current)</span></div>
                </div>
            </article>
        </section>
    </div>
</x-layouts.app>