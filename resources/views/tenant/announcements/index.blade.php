@extends('layouts.app')

@section('title', 'Bulletin Board')
@section('page-title', 'Bulletin Board')
@section('page-subtitle', 'Announcements from your boarding house admin.')

@section('content')
    <div class="max-w-3xl space-y-4">
        @forelse ($announcements as $announcement)
            <article id="announcement-{{ $announcement->id }}" class="bulletin-post scroll-mt-24 rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                @if ($announcement->is_pinned)
                    <p class="bulletin-pin">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17v5M9 4h6l-1 6 3 3H7l3-3-1-6Z"/></svg>
                        Pinned
                    </p>
                @endif
                <h2 class="font-semibold text-slate-950">{{ $announcement->title }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $announcement->author?->name ?? 'Admin' }} · {{ $announcement->created_at->format('M j, Y g:i A') }}</p>
                <div class="bulletin-body mt-3 text-sm text-slate-700">{!! nl2br(e($announcement->body)) !!}</div>
            </article>
        @empty
            <div class="rounded-lg bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-950">No announcements yet</h2>
                <p class="mt-1 text-sm text-slate-600">When the admin posts an update, it will show up here and in your notifications.</p>
            </div>
        @endforelse

        <div>{{ $announcements->links() }}</div>
    </div>
@endsection
