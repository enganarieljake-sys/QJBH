<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Payment method</label>
            <input id="name" name="name" value="{{ old('name', $channel->name) }}" required maxlength="60" placeholder="GCash" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="account_name" class="block text-sm font-medium text-slate-700">Account name</label>
            <input id="account_name" name="account_name" value="{{ old('account_name', $channel->account_name) }}" required maxlength="120" placeholder="Jackie S. Pepito" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-2">
            <label for="account_number" class="block text-sm font-medium text-slate-700">Account / mobile number</label>
            <input id="account_number" name="account_number" value="{{ old('account_number', $channel->account_number) }}" required maxlength="60" inputmode="numeric" placeholder="0917 123 4567" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-2">
            <label for="qr" class="block text-sm font-medium text-slate-700">QR code image</label>
            @if ($channel->qr_path)
                <img src="{{ route('payment-channels.qr', $channel) }}" alt="Current QR code" class="payment-qr mt-2 w-40">
                <p class="mt-2 text-xs text-slate-500">Upload a new image only if you want to replace this QR.</p>
            @endif
            <input id="qr" type="file" name="qr" accept="image/png,image/jpeg,image/webp" @required(! $channel->qr_path) class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WEBP up to 4 MB. Tip: screenshot the QR from your GCash app (open Receive, then QR).</p>
        </div>
        <div class="md:col-span-2">
            <label for="instructions" class="block text-sm font-medium text-slate-700">Instructions for tenants <span class="text-slate-500">(optional)</span></label>
            <textarea id="instructions" name="instructions" rows="3" maxlength="1000" placeholder="Please put your room number in the GCash message." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('instructions', $channel->instructions) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 md:col-span-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $channel->is_active)) class="h-4 w-4">
            Show this QR to tenants
        </label>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ $submitLabel }}</button>
        <a href="{{ route('admin.payment-channels.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
