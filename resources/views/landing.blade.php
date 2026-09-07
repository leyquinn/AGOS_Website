<x-layouts.guest>
    <!-- Section 1: Hero (Home) -->
    <section id="home" class="pt-40 pb-24 px-6 max-w-6xl mx-auto min-h-screen flex flex-col justify-center items-center text-center">
        <span class="text-[11px] uppercase tracking-[0.2em] text-blue-400/80 font-semibold px-4 py-1.5 bg-blue-950/40 border border-blue-800/40 rounded-full mb-8">
            Undergraduate Thesis • Ateneo de Davao University
        </span>
        
        <h1 class="text-4xl md:text-6xl font-black tracking-tight text-white max-w-5xl leading-tight">
            Multi-Task NLP Framework for <span class="text-blue-500">Barangay-Level</span> Validation of Crowdsourced Flood Reports
        </h1>
        
        <p class="mt-6 text-base md:text-lg text-slate-300 max-w-3xl font-normal leading-relaxed">
            Transforming noisy, code-switched social media reports into verified, actionable flood intelligence using XLM-RoBERTa and K-Means++ consensus clustering
        </p>

        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="#" class="bg-white hover:bg-slate-100 text-slate-900 font-bold px-8 py-3.5 rounded-xl transition-all flex items-center gap-3 shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Read Thesis (PDF)
            </a>
            <a href="{{ route('login') }}" class="bg-blue-950/60 hover:bg-blue-900/60 border border-blue-800/50 text-white font-medium px-8 py-3.5 rounded-xl transition-all flex items-center gap-2">
                Explore Dashboard
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

        <div class="mt-20 flex flex-wrap justify-center gap-8 text-[11px] uppercase tracking-widest text-slate-400 font-medium">
            <span>• EN / TL / CEB Native</span>
            <span>• Unified Models</span>
            <span>• K-Means++ Validation</span>
        </div>
    </section>

    <!-- Section 2: Problem -->
    <section id="problem" class="py-28 px-6 max-w-6xl mx-auto">
        <span class="text-xs uppercase tracking-[0.2em] text-blue-400 font-bold">The Challenge</span>
        <h2 class="text-3xl md:text-5xl font-black text-white mt-3 mb-16 max-w-3xl leading-tight">
            Crowdsourced reports are fast, but rarely reliable enough for emergency response.
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <h3 class="text-sm font-bold text-red-500 uppercase tracking-wider mb-4">Noisy Data</h3>
                <p class="text-slate-300 text-sm leading-relaxed">Social posts are unstructured, code-switched, and often contain sarcasm or unrelated content.</p>
            </div>
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <h3 class="text-sm font-bold text-amber-500 uppercase tracking-wider mb-4">Fragmented</h3>
                <p class="text-slate-300 text-sm leading-relaxed">Legacy systems treat classification and location extraction as disjointed, heavy pipelines.</p>
            </div>
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider mb-4">Unverified</h3>
                <p class="text-slate-300 text-sm leading-relaxed">Without consensus-checking, LGUs cannot distinguish real events from isolated, unverifiable posts.</p>
            </div>
        </div>
    </section>

    <!-- Section 3: Process -->
    <section id="process" class="py-28 px-6 max-w-6xl mx-auto">
        <span class="text-xs uppercase tracking-[0.2em] text-blue-400 font-bold">How It Works</span>
        <h2 class="text-3xl md:text-5xl font-black text-white mt-3 mb-16 max-w-3xl leading-tight">
            One unified pipeline, from raw social feed to validated intelligence.
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="p-6 bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">01</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Harvest</h3>
                <p class="text-slate-400 text-xs leading-relaxed">Keyword / hashtag listeners pull posts from Facebook, Twitter/X, Reddit.</p>
            </div>
            <div class="p-6 bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">02</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Preprocess</h3>
                <p class="text-slate-400 text-xs leading-relaxed">Clean, normalize, tokenize, and remove duplicates.</p>
            </div>
            <div class="p-6 bg-[#0a2357] border-2 border-blue-500 rounded-2xl shadow-xl shadow-blue-500/10">
                <span class="text-xs font-bold text-blue-400">03</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Multi-task NLP</h3>
                <p class="text-slate-300 text-xs leading-relaxed">XLM-RoBERTa classifies relevance and extracts barangay-level entities, one model one pass.</p>
            </div>
            <div class="p-6 bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">04</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Consensus</h3>
                <p class="text-slate-400 text-xs leading-relaxed">K-Means++ clusters reports spatially and temporally, flagging outliers as noise.</p>
            </div>
            <div class="p-6 bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">05</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Validated Output</h3>
                <p class="text-slate-400 text-xs leading-relaxed">A confidence score, verified location, and event summary reach the LGU dashboard.</p>
            </div>
        </div>

        <!-- Formula Banner -->
        <div class="mt-8 p-6 bg-[#071738]/80 border border-blue-900/40 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h4 class="text-sm font-bold text-white">The Confidence Score Formula</h4>
                <p class="text-xs font-mono text-blue-300 mt-1">CS = w₁(Pre₁) + w₂(Dclusters)</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Source Authority Override (CS = 1.0) for verified accounts</p>
            </div>
            <a href="#" class="text-xs text-blue-400 font-semibold hover:text-blue-300 flex items-center gap-2">
                View Report Detail &rarr;
            </a>
        </div>
    </section>

    <!-- Section 4: Results -->
    <section id="results" class="py-28 px-6 max-w-6xl mx-auto">
        <span class="text-xs uppercase tracking-[0.2em] text-blue-400 font-bold">Evaluation</span>
        <h2 class="text-3xl md:text-5xl font-black text-white mt-3 mb-16 max-w-3xl leading-tight">
            Integrating K-Means++ measurably improves reliability
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Accuracy</span>
                <div class="text-5xl font-black text-white mt-3">0.93</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+12% vs Baseline</span>
            </div>
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Precision</span>
                <div class="text-5xl font-black text-white mt-3">0.90</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+13% vs Baseline</span>
            </div>
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Recall</span>
                <div class="text-5xl font-black text-white mt-3">0.88</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+14% vs Baseline</span>
            </div>
            <div class="p-8 bg-[#071738]/60 border border-blue-800/80 rounded-2xl text-center bg-blue-950/20">
                <span class="text-xs font-bold text-blue-400 uppercase tracking-widest">F1-Score</span>
                <div class="text-5xl font-black text-blue-400 mt-3">0.89</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+14% vs Baseline</span>
            </div>
        </div>
    </section>

    <!-- Section 5: Features (Interfaces) -->
    <section id="features" class="py-28 px-6 max-w-6xl mx-auto">
        <span class="text-xs uppercase tracking-[0.2em] text-blue-400 font-bold">See It In Action</span>
        <h2 class="text-3xl md:text-5xl font-black text-white mt-3 mb-16 max-w-3xl leading-tight">
            Two specialized interfaces, one validated dataset
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-[#071738]/80 border border-blue-900/40 rounded-3xl overflow-hidden flex flex-col justify-between">
                <div class="h-64 bg-[#091b40]"></div>
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-white mb-2">LGU Operations Console</h3>
                    <p class="text-slate-400 text-sm mb-6">Signal strip, live map, and model analytics for DRRMO personnel</p>
                    <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2">
                        Launch Console &rarr;
                    </a>
                </div>
            </div>

            <div class="bg-[#071738]/80 border border-blue-900/40 rounded-3xl overflow-hidden flex flex-col justify-between">
                <div class="h-64 bg-[#091b40]"></div>
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-white mb-2">Citizen Reporting App</h3>
                    <p class="text-slate-400 text-sm mb-6">Mobile-first interface for residents to submit reports and view validated alerts</p>
                    <a href="#" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2">
                        Launch Console &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Team -->
    <section id="team" class="py-28 px-6 max-w-6xl mx-auto">
        <span class="text-xs uppercase tracking-[0.2em] text-blue-400 font-bold">The Researchers</span>
        <h2 class="text-3xl md:text-5xl font-black text-white mt-3 mb-2 leading-tight">
            Bachelor of Science in Computer Science
        </h2>
        <p class="text-2xl font-bold text-slate-300 mb-16">Ateneo de Davao University</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <div class="w-16 h-16 bg-blue-950 border border-blue-800/50 text-blue-400 font-bold text-lg rounded-full mx-auto flex items-center justify-center mb-6">HC</div>
                <h3 class="text-lg font-bold text-white">Harley Love N. Cuba</h3>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">Researcher</span>
            </div>

            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <div class="w-16 h-16 bg-blue-950 border border-blue-800/50 text-blue-400 font-bold text-lg rounded-full mx-auto flex items-center justify-center mb-6">FE</div>
                <h3 class="text-lg font-bold text-white">Franz Sedrick F. Embuscado</h3>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">Researcher</span>
            </div>

            <div class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <div class="w-16 h-16 bg-blue-950 border border-blue-800/50 text-blue-400 font-bold text-lg rounded-full mx-auto flex items-center justify-center mb-6">JF</div>
                <h3 class="text-lg font-bold text-white">Jan-Aceryl N. Futalan</h3>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">Researcher</span>
            </div>
        </div>

        <p class="text-xs text-slate-500 uppercase tracking-widest text-center mt-12">
            School of Arts and Sciences • Computer Studies Cluster • November 2026
        </p>
    </section>
</x-layouts.guest>