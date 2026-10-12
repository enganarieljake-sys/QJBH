@php
    $recentNotifications = auth()->user()->notifications()->latest()->limit(8)->get();
    $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
@endphp

<div class="notification-center" data-notification-center>
    <button type="button" class="notification-toggle" data-notification-toggle aria-label="Notifications{{ $unreadNotificationCount ? ', '.$unreadNotificationCount.' unread' : '' }}" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
        @if ($unreadNotificationCount)
            <span class="notification-count">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
        @endif
    </button>

    <div class="notification-panel" data-notification-panel hidden>
        <div class="notification-heading">
            <div>
                <p class="font-bold text-slate-950">Notifications</p>
                <p class="text-xs text-slate-500">{{ $unreadNotificationCount }} unread</p>
            </div>
            @if ($unreadNotificationCount)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="notification-read-all">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="notification-list">
            @forelse ($recentNotifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                        <span class="notification-dot" aria-hidden="true"></span>
                        <span class="min-w-0 text-left">
                            <span class="block text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Update' }}</span>
                            <span class="mt-0.5 block text-xs leading-5 text-slate-600">{{ $notification->data['message'] ?? '' }}</span>
                            <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-500">No notifications yet.</div>
            @endforelse
        </div>
        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-200 px-5 py-3 text-center text-xs font-bold text-blue-600 hover:bg-slate-50">View notification history</a>
    </div>
</div>
