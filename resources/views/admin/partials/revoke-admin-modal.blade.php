<div data-revoke-admin-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border border-rose-900/50 bg-white p-6 shadow-2xl dark:bg-slate-900" role="dialog" aria-modal="true" aria-labelledby="revoke-admin-title">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-rose-500">Privilege change</p>
                <h2 id="revoke-admin-title" class="mt-2 text-xl font-black text-slate-900 dark:text-white">Revoke admin access?</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">This immediately disables portal login and invalidates active API tokens for <strong data-revoke-admin-name class="text-slate-700 dark:text-slate-200"></strong>.</p>
            </div>
            <button type="button" data-close-revoke-admin class="rounded-lg px-2 py-1 text-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close">×</button>
        </div>
        <form data-revoke-admin-form method="POST" class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
            @csrf
            <button type="button" data-close-revoke-admin class="rounded-xl px-4 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
            <button type="submit" class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white hover:bg-rose-500">Revoke Privileges</button>
        </form>
    </div>
</div>
<script>
    (() => {
        const modal = document.querySelector('[data-revoke-admin-modal]');
        const form = modal?.querySelector('[data-revoke-admin-form]');
        const name = modal?.querySelector('[data-revoke-admin-name]');
        const open = (button) => {
            form.action = button.dataset.revokeUrl;
            name.textContent = button.dataset.adminName;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        const close = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };
        document.querySelectorAll('[data-open-revoke-admin]').forEach((button) => button.addEventListener('click', () => open(button)));
        modal?.querySelectorAll('[data-close-revoke-admin]').forEach((button) => button.addEventListener('click', close));
        modal?.addEventListener('click', (event) => { if (event.target === modal) close(); });
    })();
</script>
