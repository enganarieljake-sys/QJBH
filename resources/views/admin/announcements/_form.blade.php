<form method="POST" action="{{ $action }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-4">
        <div>
            <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
            <input id="title" name="title" value="{{ old('title', $announcement->title) }}" required maxlength="150" placeholder="Water interruption this Saturday" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="body" class="block text-sm font-medium text-slate-700">Message</label>
            <textarea id="body" name="body" rows="8" required maxlength="5000" placeholder="There will be no water from 8 AM to 12 NN on Saturday due to tank cleaning. Please store water the night before." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('body', $announcement->body) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned)) class="h-4 w-4">
            Pin to the top of the bulletin board
        </label>
        @if (! $announcement->exists)
            <p class="text-xs text-slate-500">All active tenants will get a notification when you post this.</p>
        @endif
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ $submitLabel }}</button>
        <a href="{{ route('admin.announcements.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
