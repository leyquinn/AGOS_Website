<style>[x-cloak] { display: none !important; }</style>

<section x-data="{ activeTab: 'pending' }" class="citizen-status-table mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-900 dark:shadow-blue-950/20">
    <div class="border-b border-slate-200 dark:border-slate-800">
        <div class="flex flex-wrap gap-2 px-5 pt-4" role="tablist" aria-label="Citizen approval status">
            <button type="button" role="tab" @click="activeTab = 'pending'" :aria-selected="activeTab === 'pending'" :class="activeTab === 'pending' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'" class="rounded-lg px-3 py-2 text-xs font-bold transition">
                Pending <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $pendingCitizens->count() }}</span>
            </button>
            <button type="button" role="tab" @click="activeTab = 'approved'" :aria-selected="activeTab === 'approved'" :class="activeTab === 'approved' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'" class="rounded-lg px-3 py-2 text-xs font-bold transition">
                Approved <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $approvedCitizens->count() }}</span>
            </button>
            <button type="button" role="tab" @click="activeTab = 'declined'" :aria-selected="activeTab === 'declined'" :class="activeTab === 'declined' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'" class="rounded-lg px-3 py-2 text-xs font-bold transition">
                Declined <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $declinedCitizens->count() }}</span>
            </button>
        </div>
        <div class="px-5 py-4">
            <h3 class="text-lg font-black text-slate-900 dark:text-white" x-text="activeTab === 'pending' ? 'Pending Review' : (activeTab === 'approved' ? 'Approved Citizens' : 'Declined Citizens')">Pending Review</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="activeTab === 'pending' ? 'New registrations waiting for your decision.' : (activeTab === 'approved' ? 'Approved citizens who can access the Citizen App.' : 'Declined registrations and their recorded reasons.')">New registrations waiting for your decision.</p>
        </div>
    </div>

    <div x-cloak x-show="activeTab === 'pending'">
        @if ($pendingCitizens->isEmpty())
            <div class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400">No pending review.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-950/60 dark:text-slate-400">
                        <tr><th class="px-5 py-4">Citizen</th><th class="px-5 py-4">Email</th><th class="px-5 py-4">Registered</th><th class="px-5 py-4">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($pendingCitizens as $citizen)
                            <tr class="text-slate-700 dark:text-slate-200">
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">{{ $citizen->first_name }} {{ $citizen->last_name }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-300">{{ $citizen->email }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $citizen->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-4"><div class="flex flex-wrap gap-2">
                                    <form action="{{ route('admin.citizen-approvals.approve', $citizen) }}" method="POST" class="citizen-approval-form">@csrf
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-emerald-700">Approve</button>
                                    </form>
                                    <form action="{{ route('admin.citizen-approvals.decline', $citizen) }}" method="POST" class="flex gap-2">@csrf
                                        <input type="text" name="rejection_reason" maxlength="5000" placeholder="Optional reason" class="w-44 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                                        <button type="submit" class="rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-rose-700">Decline</button>
                                    </form>
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div x-cloak x-show="activeTab === 'approved'">
        @if ($approvedCitizens->isEmpty())
            <div class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400">No approved citizens.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-950/60 dark:text-slate-400">
                        <tr><th class="px-5 py-4">Citizen</th><th class="px-5 py-4">Email</th><th class="px-5 py-4">Registered</th><th class="px-5 py-4">Rejection reason</th><th class="px-5 py-4">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($approvedCitizens as $citizen)
                            <tr class="text-slate-700 dark:text-slate-200">
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">{{ $citizen->first_name }} {{ $citizen->last_name }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-300">{{ $citizen->email }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $citizen->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $citizen->rejection_reason ?: '—' }}</td>
                                <td class="px-5 py-4"><form action="{{ route('admin.citizen-approvals.decline', $citizen) }}" method="POST" class="flex gap-2">@csrf
                                    <input type="text" name="rejection_reason" maxlength="5000" placeholder="Optional reason" class="w-44 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                                    <button type="submit" class="rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-rose-700">Decline</button>
                                </form></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div x-cloak x-show="activeTab === 'declined'">
        @if ($declinedCitizens->isEmpty())
            <div class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400">No declined citizens.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-950/60 dark:text-slate-400">
                        <tr><th class="px-5 py-4">Citizen</th><th class="px-5 py-4">Email</th><th class="px-5 py-4">Registered</th><th class="px-5 py-4">Rejection reason</th><th class="px-5 py-4">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($declinedCitizens as $citizen)
                            <tr class="text-slate-700 dark:text-slate-200">
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">{{ $citizen->first_name }} {{ $citizen->last_name }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-300">{{ $citizen->email }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $citizen->created_at->format('M j, Y g:i A') }}</td>
                                <td class="max-w-xs px-5 py-4 text-slate-500 dark:text-slate-400">{{ $citizen->rejection_reason ?: '—' }}</td>
                                <td class="px-5 py-4"><form action="{{ route('admin.citizen-approvals.approve', $citizen) }}" method="POST" class="citizen-approval-form">@csrf
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-emerald-700">Approve</button>
                                </form></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
