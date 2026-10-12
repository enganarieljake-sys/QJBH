@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')
@section('immersive-dashboard', 'true')
@section('content-shell-class', 'tenant-wallet-shell')

@php
    $icons = [
        'rooms' => '<path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 8h2m2 0h2m-6 4h2m2 0h2m-6 4h6"/>',
        'tenants' => '<path d="M16 19a4 4 0 0 0-8 0"/><circle cx="12" cy="8" r="3"/><path d="M19 20a3 3 0 0 0-2.5-4.5M17 5.5a2.5 2.5 0 0 1 0 5"/>',
        'bills' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6m-6 4h3"/>',
        'receipts' => '<path d="M4 7V5a1 1 0 0 1 1-1h2M17 4h2a1 1 0 0 1 1 1v2M20 17v2a1 1 0 0 1-1 1h-2M7 20H5a1 1 0 0 1-1-1v-2M8 12l3 3 5-6"/>',
        'qr' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2M14 18h2v2M18 18h2v2"/>',
        'tools' => '<path d="m14 6 4-4 4 4-4 4m-2-2L8 16m-2-1 3 3-4 4-3-3 4-4Zm5-10 8 8"/>',
        'reports' => '<path d="M4 19h16M7 16V9m5 7V5m5 11v-4"/>',
        'bulletin' => '<path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8M8 12h5"/>',
        'concerns' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M12 8v4m0 3.5v.01"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="10" r="2"/><path d="M16 15.5a4 4 0 0 1 4.5 3.5"/>',
    ];
@endphp

@section('content')
    <section class="tenant-wallet" aria-labelledby="admin-greeting">
        <span class="tenant-wallet__glow" aria-hidden="true"></span>
        <div class="tenant-wallet__topline">
            <div>
                <p class="tenant-wallet__eyebrow">Admin workspace</p>
                <h1 id="admin-greeting">{{ $dashboardHeader['greeting'] }}, {{ auth()->user()->firstName() }}.</h1>
            </div>
            @include('partials.wallet-profile')
        </div>
        <div class="tenant-wallet__balance">
            <div>
                <p>Collected in {{ $dashboardHeader['month'] }}</p>
                <strong><span>₱</span> {{ number_format($stats['monthly_revenue'], 2) }}</strong>
            </div>
            <div class="tenant-wallet__pills">
                @if ($stats['pending_receipts'])
                    <a href="{{ route('admin.payment-submissions.index') }}" class="tenant-wallet__pill">Review {{ $stats['pending_receipts'] }} {{ \Illuminate\Support\Str::plural('receipt', $stats['pending_receipts']) }}</a>
                @else
                    <a href="{{ route('admin.payments.create') }}" class="tenant-wallet__pill">Record payment</a>
                @endif
                @staffcan('admin.analytics')
                    <a href="{{ route('admin.analytics') }}" class="tenant-wallet__pill">Analytics</a>
                @endstaffcan
            </div>
        </div>
        <div class="tenant-wallet__room">
            <span class="tenant-wallet__room-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">{!! $icons['rooms'] !!}</svg>
            </span>
            <div>
                <small>Occupancy</small>
                <b>{{ $stats['occupied_rooms'] }} of {{ $stats['total_rooms'] }} rooms occupied · {{ rtrim(rtrim(number_format($stats['occupancy_rate'], 2), '0'), '.') }}%</b>
            </div>
            <span class="tenant-wallet__status">{{ $stats['available_rooms'] }} available</span>
        </div>
    </section>

    @include('partials.dashboard-people', ['dashboardPeople' => $dashboardPeople])

    <section class="tenant-actions" aria-labelledby="admin-actions-title">
        <div class="tenant-section-heading">
            <div><p>Manage your boarding house</p><h2 id="admin-actions-title">Quick actions</h2></div>
        </div>
        <div class="tenant-actions__grid tenant-actions__grid--admin">
            @foreach ([
                ['label' => 'Rooms', 'route' => 'admin.rooms.index', 'icon' => 'solar-bold-buildings', 'badge' => null],
                ['label' => 'Tenants', 'route' => 'admin.tenants.index', 'icon' => 'solar-bold-users-group-rounded', 'badge' => null],
                ['label' => 'Bills', 'route' => 'admin.bills.index', 'icon' => 'solar-bold-bill-list', 'badge' => $stats['overdue_payments'], 'hint' => 'overdue'],
                ['label' => 'Receipts', 'route' => 'admin.payment-submissions.index', 'icon' => 'solar-bold-bill-check', 'badge' => $stats['pending_receipts'], 'hint' => 'to review'],
                ['label' => 'Payment QR', 'route' => 'admin.payment-channels.index', 'icon' => 'solar-bold-qr-code', 'badge' => null],
                ['label' => 'Repairs', 'route' => 'admin.maintenance-requests.index', 'icon' => 'solar-bold-toolbox', 'badge' => $stats['pending_maintenance'], 'hint' => 'open'],
                ['label' => 'Concerns', 'route' => 'admin.concerns.index', 'icon' => 'solar-bold-chat-round-line', 'badge' => $stats['open_concerns'], 'hint' => 'open'],
                ['label' => 'Bulletin', 'route' => 'admin.announcements.index', 'icon' => 'solar-bold-clipboard-text', 'badge' => null],
                ['label' => 'Reports', 'route' => 'admin.reports.index', 'icon' => 'solar-bold-chart-2', 'badge' => null],
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'solar-bold-user-check-rounded', 'badge' => $stats['pending_tenant_approvals'], 'hint' => 'awaiting approval'],
            ] as $action)
                @continue(! \App\Support\StaffAccess::allows(auth()->user(), $action['route']))
                <a href="{{ route($action['route']) }}" class="tenant-action"
                    @if ($action['badge']) aria-label="{{ $action['label'] }}, {{ $action['badge'] }} {{ $action['hint'] }}" @endif>
                    @if ($action['badge'])<span class="tenant-action__badge" aria-hidden="true">{{ $action['badge'] }}</span>@endif
                    <span class="tenant-action__icon" aria-hidden="true">{{ svg($action['icon']) }}</span>
                    <span>{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="tenant-highlights" aria-label="Boarding house summary">
        <article><span>Unpaid balance</span><strong>₱{{ number_format($stats['outstanding_balance'], 2) }}</strong><small>Across all open bills</small></article>
        <article><span>Overdue bills</span><strong>{{ $stats['overdue_payments'] }}</strong><small>Past their due date</small></article>
        <article><span>Active tenants</span><strong>{{ $stats['active_tenants'] }}</strong><small>of {{ $stats['total_tenants'] }} registered</small></article>
    </section>

    <div class="tenant-feed">
        @if ($pendingReceipts->isNotEmpty())
            <section class="tenant-feed__card tenant-feed__card--wide">
                <div class="flex items-center justify-between">
                    <div><p class="tenant-feed__kicker">Needs your review</p><h2>Payment receipts</h2></div>
                    <a href="{{ route('admin.payment-submissions.index') }}" class="tenant-feed__link">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
                </div>
                <div class="tenant-feed__list">
                    @foreach ($pendingReceipts as $receipt)
                        <a href="{{ route('admin.payment-submissions.show', $receipt) }}" class="tenant-feed__item">
                            <span class="tenant-feed__item-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="m9 11 2 2 4-4"/></svg></span>
                            <span><b>{{ $receipt->tenant?->full_name }}</b><small>{{ $receipt->bill?->billing_month?->format('F Y') }} · sent {{ $receipt->created_at->diffForHumans() }}</small></span>
                            <strong>₱{{ number_format($receipt->amount, 2) }}</strong>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="tenant-feed__card">
            <div class="flex items-center justify-between">
                <div><p class="tenant-feed__kicker">Latest activity</p><h2>Recent bills</h2></div>
                <a href="{{ route('admin.bills.index') }}" class="tenant-feed__link">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
            </div>
            <div class="tenant-feed__list">
                @forelse ($recentBills as $bill)
                    <a href="{{ route('admin.bills.show', $bill) }}" class="tenant-feed__item">
                        <span class="tenant-feed__item-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6m-6 4h3"/></svg></span>
                        <span><b>{{ $bill->tenant?->full_name }}</b><small>{{ $bill->billing_month?->format('F Y') }} · {{ ucfirst($bill->status) }}</small></span>
                        <strong>₱{{ number_format($bill->balance, 2) }}</strong>
                    </a>
                @empty
                    <p class="tenant-feed__empty">No bills yet.</p>
                @endforelse
            </div>
        </section>

        <section class="tenant-feed__card">
            <div class="flex items-center justify-between">
                <div><p class="tenant-feed__kicker">Keep rooms in shape</p><h2>Maintenance</h2></div>
                <a href="{{ route('admin.maintenance-requests.index') }}" class="tenant-feed__link">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
            </div>
            <div class="tenant-feed__list">
                @forelse ($recentRequests as $request)
                    <a href="{{ route('admin.maintenance-requests.show', $request) }}" class="tenant-feed__item">
                        <span class="tenant-feed__item-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9l-3.8 3.8Z"/></svg></span>
                        <span><b>Room {{ $request->room?->room_number }} · <span class="capitalize">{{ $request->status }}</span></b><small>{{ \Illuminate\Support\Str::limit($request->description, 44) }}</small></span>
                    </a>
                @empty
                    <p class="tenant-feed__empty">No maintenance requests yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
