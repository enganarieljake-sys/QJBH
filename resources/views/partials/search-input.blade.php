{{-- Search box for list pages. Sends ?q=… with the rest of the filter form. --}}
<div class="search-field {{ $class ?? 'md:col-span-full' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ $placeholder ?? 'Search…' }}" aria-label="{{ $placeholder ?? 'Search' }}" class="w-full rounded-md border border-slate-300 py-2 pl-10 pr-3 text-sm">
</div>
