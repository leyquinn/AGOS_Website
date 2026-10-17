<div data-assign-admin-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
    <div class="w-full max-w-2xl rounded-2xl border border-blue-800/60 bg-[#0b2147] p-6 text-slate-100 shadow-2xl shadow-slate-950/60" role="dialog" aria-modal="true" aria-labelledby="assign-admin-title">
        <div class="flex items-start justify-between gap-4">
            <div><h2 id="assign-admin-title" class="text-xl font-black">Assign Barangay Admin</h2><p class="mt-1 text-xs text-slate-400">The new account will be scoped to the selected barangay.</p></div>
            <button type="button" data-close-assign-admin class="rounded-lg px-2 py-1 text-xl text-slate-400 hover:bg-blue-900 hover:text-white" aria-label="Close">×</button>
        </div>
        <form action="{{ route('admin.assign-barangay-admin') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="barangay_id" class="mb-2 block text-xs font-bold text-blue-100">Barangay</label>
                <select id="barangay_id" name="barangay_id" required class="w-full rounded-xl border border-blue-900 bg-[#061633] px-3 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select a barangay</option>
                    @foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(old('barangay_id') == $barangay->id)>{{ $barangay->name }} — {{ $barangay->district }}</option>@endforeach
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="first_name" class="mb-2 block text-xs font-bold text-blue-100">First name</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-xl border border-blue-900 bg-[#061633] px-3 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label for="last_name" class="mb-2 block text-xs font-bold text-blue-100">Last name</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-xl border border-blue-900 bg-[#061633] px-3 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label for="email" class="mb-2 block text-xs font-bold text-blue-100">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-blue-900 bg-[#061633] px-3 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label for="phone_number" class="mb-2 block text-xs font-bold text-blue-100">Phone number</label><input id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required class="w-full rounded-xl border border-blue-900 bg-[#061633] px-3 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-blue-500"></div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between gap-3"><label for="password" class="block text-xs font-bold text-blue-100">Temporary password</label><button type="button" data-generate-password class="text-xs font-bold text-blue-300 hover:text-blue-200">Generate random password</button></div>
                <input id="password" name="password" type="password" minlength="8" required class="w-full rounded-xl border border-blue-900 bg-[#061633] px-3 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-3 border-t border-blue-900/70 pt-5"><button type="button" data-close-assign-admin class="rounded-xl px-4 py-3 text-sm font-bold text-slate-300 hover:bg-blue-900">Cancel</button><button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-500">Assign Admin</button></div>
        </form>
    </div>
</div>
<script>
    (() => {
        const modal = document.querySelector('[data-assign-admin-modal]');
        const open = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); modal?.querySelector('select')?.focus(); };
        const close = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };
        document.querySelector('[data-open-assign-admin]')?.addEventListener('click', open);
        modal?.querySelectorAll('[data-close-assign-admin]').forEach((button) => button.addEventListener('click', close));
        modal?.addEventListener('click', (event) => { if (event.target === modal) close(); });
        modal?.querySelector('[data-generate-password]')?.addEventListener('click', () => {
            const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
            const password = Array.from({ length: 14 }, () => alphabet[Math.floor(Math.random() * alphabet.length)]).join('');
            const input = modal.querySelector('#password');
            input.value = password;
            input.type = 'text';
        });
    })();
</script>
