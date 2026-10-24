<x-layouts.app title="Model Analytics">
    <div class="w-full" data-model-analytics>
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <p class="max-w-md text-xs leading-relaxed text-slate-400">
                Evaluation metrics for the thesis defense baseline vs. integrated pipeline.
            </p>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-amber-400/40 bg-amber-400/10 px-3 py-2 text-[9px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">Static benchmark</span>
                <select data-model-version class="rounded-full border-0 bg-[#b7c9e5] px-4 py-2 text-[10px] font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500" aria-label="Select model version">
                    <option value="v1.2">mtl-xlmr-v1.2 (current)</option>
                    <option value="v1.1">mtl-xlmr-v1.1</option>
                    <option value="v1.0">v1.0 (baseline)</option>
                </select>
            </div>
        </div>

        <section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Model summary metrics">
            @foreach ([
                ['label' => 'Accuracy', 'key' => 'accuracy', 'value' => '0.94', 'class' => 'text-slate-500'],
                ['label' => 'Precision', 'key' => 'precision', 'value' => '0.91', 'class' => 'text-slate-500'],
                ['label' => 'Recall', 'key' => 'recall', 'value' => '0.89', 'class' => 'text-slate-500'],
                ['label' => 'F1-Score', 'key' => 'f1', 'value' => '0.90', 'class' => 'text-blue-700'],
            ] as $metric)
                <article class="portal-card rounded-2xl bg-[#b7c9e5] px-4 py-3 text-center shadow-lg shadow-blue-950/10">
                    <p class="text-xs font-bold text-slate-500">{{ $metric['label'] }}</p>
                    <p data-model-metric="{{ $metric['key'] }}" class="mt-2 text-3xl font-black {{ $metric['class'] }}">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-[1.1fr_1fr]">
            <article class="portal-card rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                <h2 class="text-xs font-black">Per Barangay Performance</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[26rem] text-center text-[9px]">
                        <thead class="text-slate-500">
                            <tr><th class="pb-3 text-left font-semibold">Barangay</th><th class="pb-3 font-semibold">Precision</th><th class="pb-3 font-semibold">Recall</th><th class="pb-3 font-semibold">F1</th></tr>
                        </thead>
                        <tbody class="text-slate-500">
                            @foreach (['Poblacion', 'Matina', 'Talomo', 'Bunawan'] as $barangay)
                                <tr data-performance-row="{{ $barangay }}"><td class="py-1.5 text-left">{{ $barangay }}</td><td data-performance="precision">0.94</td><td data-performance="recall">0.91</td><td data-performance="f1">0.92</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="portal-card rounded-2xl bg-[#b7c9e5] p-4 text-slate-700 shadow-lg shadow-blue-950/10">
                <h2 class="text-xs font-black">Confusion Matrix (valid set)</h2>
                <div class="mt-5 grid grid-cols-[5rem_1fr_1fr] items-center gap-2 text-center text-[9px] text-slate-500">
                    <span></span><span>Prediction flood</span><span>Prediction non-flood</span>
                    <span class="text-left">Actual flood</span><strong data-confusion="0" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-emerald-700 dark:text-emerald-300">412</strong><strong data-confusion="1" class="rounded-lg border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-rose-700 dark:text-rose-300">38</strong>
                    <span class="text-left">Actual non-flood</span><strong data-confusion="2" class="rounded-lg border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-rose-700 dark:text-rose-300">29</strong><strong data-confusion="3" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-emerald-700 dark:text-emerald-300">551</strong>
                </div>
            </article>
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-2">
            <article class="portal-card overflow-hidden rounded-2xl bg-[#b7c9e5] text-slate-700 shadow-lg shadow-blue-950/10">
                <div class="p-4">
                    <h2 class="text-sm font-black">Baseline vs. Integrated Pipeline</h2>
                    <p class="text-[10px] text-slate-500">Classification-only (w2 = 0) vs. multi-task + K-Means++ consensus</p>
                </div>
                <div class="relative h-52 border-t border-blue-200/70 px-5 pb-3 pt-5">
                    <canvas data-model-chart="comparison" aria-label="Baseline versus integrated pipeline metrics"></canvas>
                </div>
                <div class="flex gap-5 border-t border-blue-200/70 px-5 py-3 text-[9px] text-slate-500"><span><i class="mr-1 inline-block h-2 w-2 bg-[#9bb5e1]"></i>Baseline (classification only)</span><span><i class="mr-1 inline-block h-2 w-2 bg-blue-800"></i>Integrated (MTL + K-Means++)</span></div>
            </article>

            <article class="portal-card overflow-hidden rounded-2xl bg-[#b7c9e5] text-slate-700 shadow-lg shadow-blue-950/10">
                <div class="p-4">
                    <h2 class="text-sm font-black">F1 Score Across Model Versions</h2>
                    <p class="text-[10px] text-slate-500">Improvement from active-learning retraining on reviewer feedback</p>
                </div>
                <div class="relative h-60 border-t border-blue-200/70 px-5 pb-3 pt-5">
                    <canvas data-model-chart="f1" aria-label="F1 score across model versions"></canvas>
                </div>
            </article>
        </section>
    </div>
</x-layouts.app>