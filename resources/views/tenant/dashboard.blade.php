@extends('layouts.app')

@section('title', 'Tenant Dashboard')
@section('page-title', 'Tenant Dashboard')
@section('immersive-dashboard', 'true')
@section('content-shell-class', 'tenant-wallet-shell')

@section('content')
    @if (! $tenant)
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @if ($approvalPending ?? false)
                <h2 class="font-semibold text-slate-950">Waiting for admin approval</h2>
                <p class="mt-2 text-sm text-slate-600">Your registration was received. The owner/admin must approve it before your tenant record and portal access are activated.</p>
            @else
                <h2 class="font-semibold text-slate-950">Account not linked yet</h2>
                <p class="mt-2 text-sm text-slate-600">Your login account is approved but is not linked to a tenant record. Ask the owner/admin to check Tenant Management.</p>
            @endif
        </div>
    @else
        <section class="tenant-wallet" aria-labelledby="tenant-greeting">
            <span class="tenant-wallet__glow" aria-hidden="true"></span>
            <div class="tenant-wallet__topline">
                <div>
                    <p class="tenant-wallet__eyebrow">Welcome home</p>
                    <h1 id="tenant-greeting">{{ $dashboardHeader['greeting'] }}, {{ auth()->user()->firstName() }}.</h1>
                </div>
                @include('partials.wallet-profile')
            </div>
            <div class="tenant-wallet__balance">
                <div>
                    <p>Outstanding balance</p>
                    <strong><span>₱</span> {{ number_format($stats['total_balance'], 2) }}</strong>
                </div>
                <a href="{{ route('tenant.bills.index') }}" class="tenant-wallet__pill">View bills</a>
            </div>
            <div class="tenant-wallet__room">
                <span class="tenant-wallet__room-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 8h2m2 0h2m-6 4h2m2 0h2m-6 4h6"/></svg>
                </span>
                <div><small>Your space</small><b>{{ $tenant->room ? 'Room '.$tenant->room->room_number : 'Room unassigned' }}</b></div>
                <span class="tenant-wallet__status">Active tenant</span>
            </div>
        </section>

        @if ($latestAnnouncement)
            <a href="{{ route('tenant.announcements.index') }}#announcement-{{ $latestAnnouncement->id }}" class="bulletin-teaser" aria-label="Latest announcement: {{ $latestAnnouncement->title }}. Open bulletin board">
                <span class="bulletin-teaser__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8M8 12h5"/></svg>
                </span>
                <span class="bulletin-teaser__text">
                    <small>{{ $latestAnnouncement->is_pinned ? 'Pinned announcement' : 'Latest announcement' }} · {{ $latestAnnouncement->created_at->diffForHumans() }}</small>
                    <b>{{ $latestAnnouncement->title }}</b>
                    <span>{{ \Illuminate\Support\Str::limit($latestAnnouncement->body, 90) }}</span>
                </span>
                <svg class="bulletin-teaser__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @endif

        @include('partials.dashboard-people', ['dashboardPeople' => $dashboardPeople])

        <section class="tenant-actions" aria-labelledby="quick-actions-title">
            <div class="tenant-section-heading">
                <div><p>Everything you need</p><h2 id="quick-actions-title">Quick actions</h2></div>
            </div>
            <div class="tenant-actions__grid">
                @foreach ([
                    ['label' => 'My Bills', 'route' => 'tenant.bills.index', 'icon' => 'solar-bold-bill-list', 'badge' => $stats['pending_bills'] ?: null],
                    ['label' => 'Receipt', 'route' => 'tenant.receipts.create', 'icon' => 'solar-bold-upload-minimalistic', 'badge' => null],
                    ['label' => 'Payments', 'route' => 'tenant.payments.index', 'icon' => 'solar-bold-wallet-money', 'badge' => null],
                    ['label' => 'Request Repair', 'route' => 'tenant.maintenance-requests.create', 'icon' => 'solar-bold-toolbox', 'badge' => null],
                    ['label' => 'Track Requests', 'route' => 'tenant.maintenance-requests.index', 'icon' => 'solar-bold-history', 'badge' => $stats['open_requests'] ?: null],
                    ['label' => 'Concern', 'route' => 'tenant.concerns.create', 'icon' => 'solar-bold-danger-circle', 'badge' => null],
                ] as $action)
                    <a href="{{ route($action['route']) }}" class="tenant-action">
                        @if ($action['badge'])<span class="tenant-action__badge">{{ $action['badge'] }}</span>@endif
                        <span class="tenant-action__icon" aria-hidden="true">{{ svg($action['icon']) }}</span>
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="tenant-highlights" aria-label="Account summary">
            <article><span>Total paid</span><strong>₱{{ number_format($stats['total_paid'], 2) }}</strong><small>All recorded payments</small></article>
            <article><span>Pending bills</span><strong>{{ $stats['pending_bills'] }}</strong><small>{{ $stats['pending_bills'] === 1 ? 'Bill needs' : 'Bills need' }} attention</small></article>
            <article><span>Open requests</span><strong>{{ $stats['open_requests'] }}</strong><small>Maintenance updates</small></article>
        </section>

        <div class="tenant-feed">
            <section class="tenant-feed__card">
                <div class="flex items-center justify-between">
                    <div><p class="tenant-feed__kicker">Latest activity</p><h2>Recent bills</h2></div>
                    <a href="{{ route('tenant.bills.index') }}" class="tenant-feed__link">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
                </div>
                <div class="tenant-feed__list">
                    @forelse ($recentBills as $bill)
                        <a href="{{ route('tenant.bills.show', $bill) }}" class="tenant-feed__item">
                            <span class="tenant-feed__item-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6m-6 4h3"/></svg></span>
                            <span><b>{{ $bill->billing_month?->format('F Y') }}</b><small>{{ ucfirst($bill->status) }}</small></span>
                            <strong>₱{{ number_format($bill->balance, 2) }}</strong>
                        </a>
                    @empty
                        <p class="tenant-feed__empty">No bills yet. You're all clear!</p>
                    @endforelse
                </div>
            </section>

            <section class="tenant-feed__card">
                <div class="flex items-center justify-between">
                    <div><p class="tenant-feed__kicker">Keep your room comfy</p><h2>Maintenance</h2></div>
                    <a href="{{ route('tenant.maintenance-requests.create') }}" class="tenant-feed__link">New request <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
                </div>
                <div class="tenant-feed__list">
                    @forelse ($recentRequests as $request)
                        <a href="{{ route('tenant.maintenance-requests.show', $request) }}" class="tenant-feed__item">
                            <span class="tenant-feed__item-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9l-3.8 3.8Z"/></svg></span>
                            <span><b class="capitalize">{{ $request->status }}</b><small>{{ \Illuminate\Support\Str::limit($request->description, 44) }}</small></span>
                        </a>
                    @empty
                        <p class="tenant-feed__empty">No maintenance requests yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
@endsection
