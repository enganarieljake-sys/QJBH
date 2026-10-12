{{-- Standalone search for list pages without a filter form. $keep lists query values to carry along. --}}
<form method="GET" class="mb-5 flex gap-2 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-200 {{ $class ?? '' }}" role="search">
    @foreach ($keep ?? [] as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    @include('partials.search-input', ['placeholder' => $placeholder ?? 'Search…', 'class' => 'flex-1'])
    <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Search</button>
    @if (filled(request('q')))
        <a href="{{ url()->current().(($keep ?? []) ? '?'.http_build_query($keep) : '') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
    @endif
</form>
