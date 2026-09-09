<x-layouts.app title="Settings">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8">
            <p class="max-w-xl text-sm leading-relaxed text-slate-400">Configure your AGOS portal preferences, alert delivery, and operations defaults.</p>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-5">
                <section class="rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
                    <h2 class="text-lg font-black">Portal profile</h2>
                    <p class="mt-1 text-xs text-slate-500">Update the account details shown in the operations console.</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><label for="name" class="mb-2 block text-xs font-bold text-slate-600">Full name</label><input id="name" value="DRRMO Administrator" class="w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-500"></div>
                        <div><label for="email" class="mb-2 block text-xs font-bold text-slate-600">Email address</label><input id="email" value="admin@davaocity.gov.ph" type="email" class="w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-500"></div>
                        <div><label for="role" class="mb-2 block text-xs font-bold text-slate-600">Organization role</label><select id="role" class="w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-500"><option>DRRMO Administrator</option><option>LGU Analyst</option><option>Read-only Viewer</option></select></div>
                        <div><label for="city" class="mb-2 block text-xs font-bold text-slate-600">Coverage area</label><select id="city" class="w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-500"><option>Davao City</option><option>Davao del Sur</option></select></div>
                    </div>
                </section>

                <section class="rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
                    <h2 class="text-lg font-black">Notification preferences</h2>
                    <p class="mt-1 text-xs text-slate-500">Choose which operational events should reach your team.</p>
                    <div class="mt-5 divide-y divide-blue-200/70">
                        @foreach ([['label' => 'High-confidence flood alerts', 'description' => 'Notify when a validated event reaches CS 0.80 or higher.'], ['label' => 'Pipeline health warnings', 'description' => 'Notify when queues or platform connections need attention.'], ['label' => 'Daily operations summary', 'description' => 'Receive a summary of reports, clusters, and model activity.']] as $notification)
                            <label class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"><span><span class="block text-sm font-bold">{{ $notification['label'] }}</span><span class="mt-1 block text-xs text-slate-500">{{ $notification['description'] }}</span></span><input type="checkbox" checked class="h-5 w-5 shrink-0 rounded border-slate-300 text-blue-700 focus:ring-blue-500"></label>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
                    <h2 class="text-lg font-black">Appearance</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">Your theme preference is saved on this device.</p>
                    <div class="mt-4 rounded-xl bg-slate-100 p-3 text-xs font-semibold text-slate-600"><span class="block text-blue-700">● Active theme</span><span class="mt-1 block text-slate-500">Use the moon/sun control in the header to switch modes.</span></div>
                </section>
                <section class="rounded-2xl bg-[#b7c9e5] p-5 text-slate-700 shadow-lg shadow-blue-950/10">
                    <h2 class="text-lg font-black">Harvest defaults</h2>
                    <div class="mt-4 space-y-4">
                        <div><label for="interval" class="mb-2 block text-xs font-bold text-slate-600">Harvest interval</label><select id="interval" class="w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-500"><option>2 minutes</option><option>5 minutes</option><option>10 minutes</option></select></div>
                        <div><label for="threshold" class="mb-2 block text-xs font-bold text-slate-600">Alert confidence threshold</label><select id="threshold" class="w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-500"><option>0.80</option><option>0.70</option><option>0.90</option></select></div>
                    </div>
                </section>
                <button type="button" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">Save changes</button>
            </aside>
        </div>
    </div>
</x-layouts.app>