<section class="danger-zone mt-10 max-w-xl rounded-lg p-6" aria-labelledby="danger-zone-heading">
    <h2 id="danger-zone-heading" class="font-semibold text-rose-600">Danger zone: reset all data</h2>
    <p class="mt-2 text-sm text-slate-600">
        This permanently deletes <b>all tenants and their accounts, bills, payments, receipts, maintenance requests, concerns, visitors, announcements, notifications, meter readings, and expenses</b>.
        Admin accounts, rooms, payment QR codes, settings, house rules, and the landing page are kept. <b>This cannot be undone.</b>
    </p>

    <form method="POST" action="{{ route('admin.data-reset.code') }}" class="mt-4">
        @csrf
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
            {{ session('reset_code_sent') ? 'Send a new code' : 'Step 1: Email me a reset code' }}
        </button>
        <p class="mt-1 text-xs text-slate-500">The code goes to {{ \App\Http\Controllers\Admin\DataResetController::codeRecipient(auth()->user()->email) }}.</p>
    </form>

    @if (session('reset_code_sent') || $errors->reset->any())
        <form method="POST" action="{{ route('admin.data-reset.destroy') }}" class="mt-6 space-y-4 border-t border-rose-200 pt-4" onsubmit="return confirm('Last chance: permanently delete all tenant and billing data?')">
            @csrf
            @method('DELETE')
            <p class="text-sm font-semibold text-slate-950">Step 2: Confirm</p>
            <div>
                <label for="reset_code" class="block text-sm font-medium text-slate-700">6-digit code from your email</label>
                <input id="reset_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required class="mt-1 w-40 rounded-md border border-slate-300 px-3 py-2 text-sm tracking-widest">
                @error('code', 'reset') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="reset_password" class="block text-sm font-medium text-slate-700">Your password</label>
                <input id="reset_password" name="password" type="password" autocomplete="current-password" required class="mt-1 w-full max-w-sm rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('password', 'reset') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="reset_confirmation" class="block text-sm font-medium text-slate-700">Type <b>RESET</b> to confirm</label>
                <input id="reset_confirmation" name="confirmation" autocomplete="off" required class="mt-1 w-40 rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('confirmation', 'reset') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <button class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Permanently Reset All Data</button>
        </form>
    @endif
</section>
