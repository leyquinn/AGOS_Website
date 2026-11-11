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
            <div class="system-notice system-notice-success mb-5 rounded-xl border px-4 py-3 text-sm font-semibold" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="system-notice system-notice-error mb-5 rounded-xl border px-4 py-3 text-sm" role="alert">{{ $errors->first() }}</div>
        @endif

        @include('admin.partials.citizen-status-table', [
            'pendingCitizens' => $citizens,
            'approvedCitizens' => $approvedCitizens,
            'declinedCitizens' => $declinedCitizens,
        ])
    </div>

    <script>
        document.querySelectorAll('.citizen-approval-form').forEach((form) => {
            form.addEventListener('submit', () => {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const input = form.querySelector('input[name="_token"]');

                if (token && input) {
                    input.value = token;
                }
            });
        });
    </script>
</x-layouts.app>
