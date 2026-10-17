<x-layouts.app title="Barangay Management">
    <div class="w-full">
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-300">Super Admin Portal</p>
                <h2 class="mt-2 text-2xl font-black text-white">Barangay Admin Management</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">Assign one or more barangay administrators and keep each administrator scoped to their assigned Davao City barangay.</p>
            </div>
            <button type="button" data-open-assign-admin class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">+ Assign Barangay Admin</button>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm font-semibold text-emerald-200" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100" role="alert">
                <p class="font-bold">Unable to assign the barangay admin.</p>
                <ul class="mt-2 list-inside list-disc text-xs">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-blue-900/50 bg-[#0b2147] shadow-xl shadow-blue-950/20">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-blue-950/60 text-[10px] uppercase tracking-wider text-blue-200">
                        <tr><th class="px-5 py-4">Barangay</th><th class="px-5 py-4">District</th><th class="px-5 py-4">Assigned Admin</th><th class="px-5 py-4">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-blue-900/50">
                        @foreach ($barangays as $barangay)
                            @php($admin = $barangay->users->first())
                            <tr class="text-slate-200">
                                <td class="px-5 py-4 font-bold">{{ $barangay->name }}</td>
                                <td class="px-5 py-4 text-slate-400">{{ $barangay->district }}</td>
                                <td class="px-5 py-4">
                                    @if ($admin)
                                        <div class="font-semibold">{{ $admin->first_name }} {{ $admin->last_name }}</div>
                                        <div class="mt-1 text-xs text-slate-400">{{ $admin->email }}</div>
                                    @else
                                        <span class="text-slate-500">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($admin)
                                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-300">{{ $admin->is_active ? 'Active' : 'Inactive' }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-500/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Unassigned</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @include('admin.partials.assign-admin-modal', ['barangays' => $barangays])
</x-layouts.app>
