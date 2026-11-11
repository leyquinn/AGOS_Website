<x-layouts.app title="Barangay Management">
    <style>[x-cloak] { display: none !important; }</style>
    <div class="w-full">
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-300">Super Admin Portal</p>
                <h2 class="mt-2 text-2xl font-black text-white">Barangay Admin Management</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">Assign one or more barangay administrators and keep each administrator scoped to their assigned Davao City barangay.</p>
            </div>
            <button type="button" data-open-assign-admin class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">Assign Barangay Admin</button>
        </div>

        @if (session('success'))
            <div class="system-notice system-notice-success mb-5 rounded-xl border px-4 py-3 text-sm font-semibold" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="system-notice system-notice-error mb-5 rounded-xl border px-4 py-3 text-sm">
                <p class="font-bold">Unable to assign the barangay admin.</p>
                <ul class="mt-2 list-inside list-disc text-xs">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section class="barangay-admin-table-card overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="barangay-admin-table w-full min-w-[860px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-950/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-4">Barangay</th>
                            <th class="px-6 py-4">District</th>
                            <th class="px-6 py-4">Assigned Admin</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($barangays as $barangay)
                            @php
                                $admin = $barangay->users->first(fn ($user) => $user->is_active && $user->status === 'approved');
                                $revokedAdmin = $barangay->users->first(fn ($user) => $user->status === 'revoked');
                                $displayAdmin = $admin ?? $revokedAdmin;
                            @endphp
                            <tr class="transition-colors duration-150 hover:bg-slate-50/80 dark:text-slate-200 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-5 font-bold text-slate-800 dark:text-slate-100">{{ $barangay->name }}</td>
                                <td class="px-6 py-5 text-slate-500 dark:text-slate-400">{{ $barangay->district }}</td>
                                <td class="px-6 py-5">
                                    @if ($displayAdmin)
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ strtoupper(substr($displayAdmin->first_name, 0, 1).substr($displayAdmin->last_name, 0, 1)) }}</span>
                                            <div>
                                                <div class="font-bold text-slate-800 dark:text-slate-100">{{ $displayAdmin->first_name }} {{ $displayAdmin->last_name }}</div>
                                                <div class="mt-0.5 text-xs text-slate-500">{{ $displayAdmin->email }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="font-normal italic text-slate-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-center">
                                    @if ($admin)
                                        <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active</span>
                                    @elseif ($revokedAdmin)
                                        <span class="inline-flex rounded-full border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Revoked</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-slate-500/20 bg-slate-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-right">
                                    @if ($admin)
                                        <div x-data="{ open: false }" @click.outside="open = false" class="relative inline-flex items-center gap-2">
                                            <button type="button" data-open-assign-admin data-barangay-id="{{ $barangay->id }}" class="rounded-lg px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-500/10">Reassign</button>
                                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="barangay-admin-more-button rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="More actions">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <circle cx="10" cy="3.5" r="1.5"></circle>
                                                    <circle cx="10" cy="10" r="1.5"></circle>
                                                    <circle cx="10" cy="16.5" r="1.5"></circle>
                                                </svg>
                                            </button>
                                            <div x-cloak x-show="open" x-transition class="absolute right-0 top-full z-20 mt-2 w-44 rounded-xl border border-slate-200 bg-white p-1 text-left shadow-lg shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900">
                                                <button type="button" data-open-revoke-admin data-admin-name="{{ $admin->first_name }} {{ $admin->last_name }}" data-revoke-url="{{ route('admin.barangay.revoke', $admin) }}" @click="open = false" class="block w-full rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">Revoke Access</button>
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" data-open-assign-admin data-barangay-id="{{ $barangay->id }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-500">Assign Admin</button>
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
    @include('admin.partials.revoke-admin-modal')
</x-layouts.app>
