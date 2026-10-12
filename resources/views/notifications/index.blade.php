@extends('layouts.app')

@section('title', 'Notification History')
@section('page-title', 'Notification History')
@section('page-subtitle', 'Your announcements, billing updates, repairs, and account activity.')

@section('page-actions')
    @if ($unreadCount)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Mark all read</button>
        </form>
    @endif
@endsection

@section('content')
    <div class="max-w-3xl overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        @forelse ($notifications as $notification)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="border-b border-slate-100 last:border-0">
                @csrf
                <button class="flex w-full items-start gap-3 px-5 py-4 text-left hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-blue-50/60' }}">
                    <span class="mt-2 size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-blue-600' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-slate-950">{{ $notification->data['title'] ?? 'Update' }}</span>
                        <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</span>
                        <span class="mt-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $notification->created_at->format('M j, Y g:i A') }} · {{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            </form>
        @empty
            <div class="px-6 py-12 text-center">
                <h2 class="font-semibold text-slate-950">No notifications yet</h2>
                <p class="mt-1 text-sm text-slate-600">New announcements and activity will appear here.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-5 max-w-3xl">{{ $notifications->links() }}</div>
@endsection
