<x-layouts.app title="Citizen Approvals">
    <div class="w-full">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-300">Barangay Admin Portal</p>
            <h2 class="mt-2 text-2xl font-black text-white">Citizen Registration Approvals</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">
                Review citizen registrations assigned to {{ auth()->user()->barangay?->name ?? 'your barangay' }}.
                Approved and declined records remain available for review and status changes.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm font-semibold text-emerald-200" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100" role="alert">{{ $errors->first() }}</div>
        @endif

        @include('admin.partials.citizen-status-table', [
            'title' => 'Pending Review',
            'description' => 'New registrations waiting for your decision.',
            'citizens' => $citizens,
            'status' => 'pending',
        ])

        @include('admin.partials.citizen-status-table', [
            'title' => 'Approved Citizens',
            'description' => 'Approved citizens who can access the Citizen App.',
            'citizens' => $approvedCitizens,
            'status' => 'approved',
        ])

        @include('admin.partials.citizen-status-table', [
            'title' => 'Declined Citizens',
            'description' => 'Declined registrations and their recorded reasons.',
            'citizens' => $declinedCitizens,
            'status' => 'declined',
        ])
    </div>
</x-layouts.app>
