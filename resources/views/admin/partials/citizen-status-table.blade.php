<section class="mb-6 overflow-hidden rounded-2xl border border-blue-900/50 bg-[#0b2147] shadow-xl shadow-blue-950/20">
    <div class="border-b border-blue-900/50 px-5 py-4">
        <h3 class="text-lg font-black text-white">{{ $title }}</h3>
        <p class="mt-1 text-xs text-slate-400">{{ $description }}</p>
    </div>

    @if ($citizens->isEmpty())
        <div class="px-6 py-8 text-sm text-slate-500">No {{ strtolower($title) }}.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-blue-950/60 text-[10px] uppercase tracking-wider text-blue-200">
                    <tr>
                        <th class="px-5 py-4">Citizen</th>
                        <th class="px-5 py-4">Email</th>
                        <th class="px-5 py-4">Registered</th>
                        <th class="px-5 py-4">Rejection reason</th>
                        <th class="px-5 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-900/50">
                    @foreach ($citizens as $citizen)
                        <tr class="text-slate-200">
                            <td class="px-5 py-4 font-bold">{{ $citizen->first_name }} {{ $citizen->last_name }}</td>
                            <td class="px-5 py-4 text-slate-300">{{ $citizen->email }}</td>
                            <td class="px-5 py-4 text-slate-400">{{ $citizen->created_at->format('M j, Y g:i A') }}</td>
                            <td class="max-w-xs px-5 py-4 text-slate-400">{{ $citizen->rejection_reason ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if ($status !== 'approved')
                                        <form action="{{ route('admin.citizen-approvals.approve', $citizen) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500">Approve</button>
                                        </form>
                                    @endif
                                    @if ($status !== 'declined')
                                        <form action="{{ route('admin.citizen-approvals.decline', $citizen) }}" method="POST" class="flex gap-2">
                                            @csrf
                                            <input type="text" name="rejection_reason" maxlength="5000" placeholder="Optional reason" class="w-44 rounded-lg border border-blue-900 bg-[#061633] px-3 py-2 text-xs text-white placeholder-slate-500">
                                            <button type="submit" class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-bold text-white hover:bg-rose-600">Decline</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
