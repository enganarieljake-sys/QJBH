@extends('layouts.app')

@section('title', 'Bulletin Board')
@section('page-title', 'Bulletin Board')
@section('page-subtitle', 'Post announcements for your tenants. Every new post notifies all '.$tenantCount.' active '.\Illuminate\Support\Str::plural('tenant', $tenantCount).'.')
@section('page-actions')
    <a href="{{ route('admin.announcements.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Post Announcement</a>
@endsection

@section('content')
    @include('partials.search-bar', ['placeholder' => 'Search announcements…', 'class' => 'max-w-3xl'])
    <div class="max-w-3xl space-y-4">
        @forelse ($announcements as $announcement)
            <article class="bulletin-post rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        @if ($announcement->is_pinned)
                            <p class="bulletin-pin">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17v5M9 4h6l-1 6 3 3H7l3-3-1-6Z"/></svg>
                                Pinned
                            </p>
                        @endif
                        <h2 class="font-semibold text-slate-950">{{ $announcement->title }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $announcement->author?->name ?? 'Admin' }} · {{ $announcement->created_at->format('M j, Y g:i A') }}@if ($announcement->updated_at->gt($announcement->created_at->addMinute())) · edited @endif</p>
                    </div>
                </div>
                <div class="bulletin-body mt-3 text-sm text-slate-700">{!! nl2br(e($announcement->body)) !!}</div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Edit</a>
                    @staffcan('admin.announcements.destroy')
                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                    </form>
                    @endstaffcan
                </div>
            </article>
        @empty
            <div class="rounded-lg bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                @if (filled(request('q')))
                    <h2 class="font-semibold text-slate-950">No announcements match "{{ request('q') }}"</h2>
                @else
                    <h2 class="font-semibold text-slate-950">No announcements yet</h2>
                    <p class="mt-1 text-sm text-slate-600">Post house rules, water or power interruptions, payment reminders, or events.</p>
                    <a href="{{ route('admin.announcements.create') }}" class="mt-4 inline-block rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Post Announcement</a>
                @endif
            </div>
        @endforelse

        <div>{{ $announcements->links() }}</div>
    </div>
@endsection
