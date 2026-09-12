<x-layouts.guest>
    <!-- Section 1: Hero (Home) -->
    <section id="home" data-scroll-fade class="relative isolate w-full overflow-hidden px-5 sm:px-8 lg:px-12 pt-32 sm:pt-40 pb-24 min-h-screen flex flex-col justify-center items-center text-center">
        <!-- Replace this temporary background layer with your hero image later. -->
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_50%_35%,rgba(37,99,235,0.42),transparent_38%),linear-gradient(135deg,#071738_0%,#031027_55%,#020b1a_100%)]"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-30 [background-image:linear-gradient(rgba(96,165,250,0.14)_1px,transparent_1px),linear-gradient(90deg,rgba(96,165,250,0.14)_1px,transparent_1px)] [background-size:42px_42px]"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-[#031027]/10 via-[#031027]/30 to-[#031027]"></div>

        <span class="inline-flex items-center gap-2 text-[11px] uppercase tracking-[0.2em] text-blue-200 font-semibold px-4 py-1.5 bg-blue-950/60 border border-blue-400/40 rounded-full mb-8">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.8)]"></span>
            Flood intelligence, built for the barangay
        </span>
        
        <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight text-white max-w-5xl leading-tight">
            From flood reports to <span class="text-blue-400">clearer decisions.</span>
        </h1>
        
        <p class="mt-6 text-base sm:text-lg md:text-xl text-slate-300 max-w-3xl font-normal leading-relaxed">
            AGOS turns noisy, code-switched community reports into validated flood intelligence that helps local response teams act with confidence.
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
            <span>EN / TL / CEB ready</span>
            <span>Unified NLP pipeline</span>
            <span>Consensus-checked signals</span>
        </div>
        <div class="mt-12 grid w-full max-w-3xl grid-cols-1 gap-3 text-left sm:grid-cols-3">
            <div class="rounded-2xl border border-blue-800/50 bg-[#071738]/70 p-4 backdrop-blur-sm">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">Signal status</span>
                <span class="flex items-center gap-2 text-sm font-semibold text-white"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Monitoring active</span>
            </div>
            <div class="rounded-2xl border border-blue-800/50 bg-[#071738]/70 p-4 backdrop-blur-sm">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">Built for</span>
                <span class="text-sm font-semibold text-white">DRRMO &amp; LGU teams</span>
            </div>
            <div class="rounded-2xl border border-blue-800/50 bg-[#071738]/70 p-4 backdrop-blur-sm">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">Core output</span>
                <span class="text-sm font-semibold text-white">Verified local context</span>
            </div>
        </div>
    </section>

    <!-- Section 2: Problem -->
    <section id="problem" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">The Challenge</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-5 max-w-3xl leading-tight">
            The first signal is not always the clearest one.
        </h2>
        <p class="mb-12 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-16 sm:text-lg">When every minute matters, responders need more than a stream of posts. They need context, confidence, and a location they can trust.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <span class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-red-500/10 text-red-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"></path></svg></span>
                <h3 class="text-sm font-bold text-red-500 uppercase tracking-wider mb-4">Noisy Data</h3>
                <p class="text-base leading-relaxed text-slate-300">Social posts are unstructured, code-switched, and often contain sarcasm or unrelated content.</p>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <span class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 5h8m-8 5h5M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"></path></svg></span>
                <h3 class="text-sm font-bold text-amber-500 uppercase tracking-wider mb-4">Fragmented</h3>
                <p class="text-base leading-relaxed text-slate-300">Legacy systems treat classification and location extraction as disjointed, heavy pipelines.</p>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl">
                <span class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.62-2.19a9 9 0 11-3.43-3.43"></path></svg></span>
                <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider mb-4">Unverified</h3>
                <p class="text-base leading-relaxed text-slate-300">Without consensus-checking, LGUs cannot distinguish real events from isolated, unverifiable posts.</p>
            </div>
        </div>
    </section>

    <!-- Section 3: Process -->
    <section id="process" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">How It Works</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-12 sm:mb-16 max-w-3xl leading-tight">
            One unified pipeline, from raw social feed to validated intelligence.
        </h2>

        <div class="mb-5 flex items-center justify-between">
            <button type="button" data-carousel-prev="process-carousel" class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:text-blue-200 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Previous process step">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button type="button" data-carousel-next="process-carousel" class="rounded-full border border-blue-800/50 bg-blue-950/40 p-2 text-blue-300 transition hover:border-blue-500 hover:text-blue-200 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Next process step">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>

        <div id="process-carousel" data-carousel class="overflow-hidden pb-3" aria-label="How it works process steps">
            <div data-carousel-track class="flex gap-4 transition-transform duration-1000 ease-in-out">
            <div class="min-w-[85%] shrink-0 snap-start p-6 sm:min-w-[45%] lg:min-w-[32%] bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">01</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Harvest</h3>
                <p class="text-sm sm:text-base text-slate-400 leading-relaxed">Keyword / hashtag listeners pull posts from Facebook, Twitter/X, Reddit.</p>
            </div>
            <div class="min-w-[85%] shrink-0 snap-start p-6 sm:min-w-[45%] lg:min-w-[32%] bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">02</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Preprocess</h3>
                <p class="text-sm sm:text-base text-slate-400 leading-relaxed">Clean, normalize, tokenize, and remove duplicates.</p>
            </div>
            <div class="min-w-[85%] shrink-0 snap-start p-6 sm:min-w-[45%] lg:min-w-[32%] bg-[#0a2357] border-2 border-blue-500 rounded-2xl shadow-xl shadow-blue-500/10">
                <span class="text-xs font-bold text-blue-400">03</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Multi-task NLP</h3>
                <p class="text-sm sm:text-base text-slate-300 leading-relaxed">XLM-RoBERTa classifies relevance and extracts barangay-level entities, one model one pass.</p>
            </div>
            <div class="min-w-[85%] shrink-0 snap-start p-6 sm:min-w-[45%] lg:min-w-[32%] bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">04</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Consensus</h3>
                <p class="text-sm sm:text-base text-slate-400 leading-relaxed">K-Means++ clusters reports spatially and temporally, flagging outliers as noise.</p>
            </div>
            <div class="min-w-[85%] shrink-0 snap-start p-6 sm:min-w-[45%] lg:min-w-[32%] bg-[#071738]/40 border border-blue-900/30 rounded-2xl">
                <span class="text-xs font-bold text-slate-400">05</span>
                <h3 class="text-base font-bold text-white uppercase mt-4 mb-3">Validated Output</h3>
                <p class="text-sm sm:text-base text-slate-400 leading-relaxed">A confidence score, verified location, and event summary reach the LGU dashboard.</p>
            </div>
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
    <section id="results" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">Evaluation</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-5 max-w-3xl leading-tight">
            Better signals lead to better response.
        </h2>
        <p class="mb-12 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-16 sm:text-lg">A quick look at the model’s evaluation results, designed to make the value of validated reporting easy to understand.</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Accuracy</span>
                <div class="text-5xl font-black text-white mt-3">0.93</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+12% vs Baseline</span>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Precision</span>
                <div class="text-5xl font-black text-white mt-3">0.90</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+13% vs Baseline</span>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-900/40 rounded-2xl text-center">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Recall</span>
                <div class="text-5xl font-black text-white mt-3">0.88</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+14% vs Baseline</span>
            </div>
            <div data-landing-card class="p-8 bg-[#071738]/60 border border-blue-800/80 rounded-2xl text-center bg-blue-950/20">
                <span class="text-xs font-bold text-blue-400 uppercase tracking-widest">F1-Score</span>
                <div class="text-5xl font-black text-blue-400 mt-3">0.89</div>
                <span class="text-xs font-semibold text-emerald-400 mt-2 block">+14% vs Baseline</span>
            </div>
        </div>
    </section>

    <!-- Section 5: Features (Interfaces) -->
    <section id="features" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">See It In Action</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-5 max-w-3xl leading-tight">
            One trusted picture, from two sides of the story.
        </h2>
        <p class="mb-12 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-16 sm:text-lg">AGOS connects the people who report what they see with the teams who decide what happens next.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div data-landing-card class="bg-[#071738]/80 border border-blue-900/40 rounded-3xl overflow-hidden flex flex-col justify-between">
                <div class="relative h-64 overflow-hidden bg-[#091b40] p-6">
                    <div class="absolute inset-x-8 top-8 h-2 rounded-full bg-blue-900/80"></div>
                    <div class="absolute left-8 top-20 h-28 w-2/3 rounded-2xl border border-blue-700/50 bg-blue-600/20"></div>
                    <div class="absolute right-8 top-20 space-y-3">
                        <span class="block h-3 w-20 rounded-full bg-emerald-400/80"></span>
                        <span class="block h-3 w-14 rounded-full bg-blue-400/70"></span>
                        <span class="block h-3 w-24 rounded-full bg-slate-600"></span>
                    </div>
                    <span class="absolute bottom-6 left-8 text-[10px] font-bold uppercase tracking-widest text-blue-300">Live operations view</span>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-white mb-2">LGU Operations Console</h3>
                    <p class="text-slate-400 text-sm mb-6">Signal strip, live map, and model analytics for DRRMO personnel</p>
                    <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2">
                        Launch Console &rarr;
                    </a>
                </div>
            </div>

            <div data-landing-card class="bg-[#071738]/80 border border-blue-900/40 rounded-3xl overflow-hidden flex flex-col justify-between">
                <div class="relative h-64 overflow-hidden bg-[#091b40] p-6">
                    <div class="absolute left-1/2 top-6 h-48 w-28 -translate-x-1/2 rounded-[1.5rem] border-4 border-blue-800 bg-[#071738] p-3 shadow-2xl shadow-blue-950/50">
                        <span class="mb-4 block h-2 w-10 rounded-full bg-slate-600 mx-auto"></span>
                        <span class="mb-3 block h-16 rounded-xl bg-blue-600/30"></span>
                        <span class="mb-2 block h-2 w-16 rounded-full bg-slate-500"></span>
                        <span class="block h-2 w-20 rounded-full bg-slate-700"></span>
                    </div>
                    <span class="absolute bottom-6 left-8 text-[10px] font-bold uppercase tracking-widest text-blue-300">Community reporting view</span>
                </div>
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
    <section id="team" data-scroll-fade class="w-full px-5 sm:px-8 lg:px-12 py-20 sm:py-28">
        <span class="text-sm uppercase tracking-[0.2em] text-blue-400 font-bold">The Researchers</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mt-3 mb-2 leading-tight">
            Bachelor of Science in Computer Science
        </h2>
        <p class="text-xl sm:text-2xl font-bold text-slate-300 mb-5">Ateneo de Davao University</p>
        <p class="mb-16 max-w-2xl text-base leading-relaxed text-slate-400">A student-built research platform focused on making local flood intelligence more useful, explainable, and actionable.</p>

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