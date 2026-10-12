<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Boarding House Management System')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/queen-jackie-favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="QJBH App">
    <script>
        (() => {
            const savedTheme = localStorage.getItem('unistay-theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.dataset.theme = savedTheme || systemTheme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="secure-theme min-h-screen bg-slate-100 text-slate-900 antialiased" data-dashboard-color="{{ auth()->check() ? auth()->user()->dashboardColor() : 'blue' }}">
    @auth
        @php
            $isAdmin = auth()->user()->isStaff();
            $navLinks = $isAdmin
                ? [
                    ['icon' => 'solar-outline-widget', 'label' => 'Dashboard & Reports', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard', 'admin.reports.*', 'admin.analytics']],
                    ['icon' => 'solar-outline-chat-round-dots', 'label' => 'Ask QJ Bot', 'route' => 'admin.assistant.index', 'match' => 'admin.assistant.*'],
                    ['icon' => 'solar-outline-buildings', 'section' => 'Tenants', 'label' => 'Rooms', 'route' => 'admin.rooms.index', 'match' => 'admin.rooms.*'],
                    ['icon' => 'solar-outline-users-group-rounded', 'label' => 'Tenants', 'route' => 'admin.tenants.index', 'match' => 'admin.tenants.*'],
                    ['icon' => 'solar-outline-user-check-rounded', 'label' => 'Users & Approvals', 'route' => 'admin.users.index', 'match' => 'admin.users.*'],
                    ['icon' => 'solar-outline-user-id', 'label' => 'Visitor Log', 'route' => 'admin.visitors.index', 'match' => 'admin.visitors.*'],
                    ['icon' => 'solar-outline-wallet-money', 'section' => 'Money', 'label' => 'Bills & Payments', 'route' => 'admin.bills.index', 'match' => ['admin.bills.*', 'admin.payments.*', 'admin.payment-channels.*', 'admin.payment-submissions.*']],
                    ['icon' => 'solar-outline-bolt', 'label' => 'Utilities & Meters', 'route' => 'admin.utilities.index', 'match' => 'admin.utilities.*'],
                    ['icon' => 'solar-outline-calculator', 'label' => 'Expenses', 'route' => 'admin.expenses.index', 'match' => 'admin.expenses.*'],
                    ['icon' => 'solar-outline-clipboard-text', 'section' => 'Communication', 'label' => 'Bulletin Board', 'route' => 'admin.announcements.index', 'match' => 'admin.announcements.*'],
                    ['icon' => 'solar-outline-checklist', 'label' => 'Daily Checklist', 'route' => 'admin.checklists.index', 'match' => 'admin.checklists.*'],
                    ['icon' => 'solar-outline-toolbox', 'label' => 'Repairs', 'route' => 'admin.maintenance-requests.index', 'match' => 'admin.maintenance-requests.*'],
                    ['icon' => 'solar-outline-history', 'label' => 'Maintenance History', 'route' => 'admin.maintenance-history.index', 'match' => 'admin.maintenance-history.*'],
                    ['icon' => 'solar-outline-chat-round-line', 'label' => 'Concerns', 'route' => 'admin.concerns.index', 'match' => 'admin.concerns.*'],
                    ['icon' => 'solar-outline-gallery-minimalistic', 'section' => 'Website', 'label' => 'Landing Page', 'route' => 'admin.landing.edit', 'match' => 'admin.landing.*'],
                    ['icon' => 'solar-outline-settings-minimalistic', 'label' => 'Settings & House Rules', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*'],
                ]
                : [
                    ['icon' => 'solar-outline-widget', 'label' => 'Dashboard', 'route' => 'tenant.dashboard', 'match' => 'tenant.dashboard'],
                    ['icon' => 'solar-outline-bill-list', 'section' => 'Money', 'label' => 'My Bills', 'route' => 'tenant.bills.index', 'match' => 'tenant.bills.*'],
                    ['icon' => 'solar-outline-card', 'label' => 'My Payments', 'route' => 'tenant.payments.index', 'match' => ['tenant.payments.*', 'tenant.receipts.*']],
                    ['icon' => 'solar-outline-clipboard-text', 'section' => 'House', 'label' => 'Bulletin Board', 'route' => 'tenant.announcements.index', 'match' => 'tenant.announcements.*'],
                    ['icon' => 'solar-outline-toolbox', 'label' => 'Repairs', 'route' => 'tenant.maintenance-requests.index', 'match' => 'tenant.maintenance-requests.*'],
                    ['icon' => 'solar-outline-chat-round-line', 'label' => 'Concerns', 'route' => 'tenant.concerns.index', 'match' => 'tenant.concerns.*'],
                    ['icon' => 'solar-outline-user-id', 'label' => 'Visitors', 'route' => 'tenant.visitors.index', 'match' => 'tenant.visitors.*'],
                    ['icon' => 'solar-outline-book-minimalistic', 'label' => 'House Rules', 'route' => 'house-rules', 'match' => 'house-rules'],
                ];

            // Caretakers and maintenance staff only see the pages their role can open;
            // a section heading moves to the first link still shown under it.
            if ($isAdmin && ! auth()->user()->isAdmin()) {
                $visible = [];
                $section = null;
                $shownSection = null;

                foreach ($navLinks as $link) {
                    $section = $link['section'] ?? $section;
                    unset($link['section']);

                    if (! \App\Support\StaffAccess::allows(auth()->user(), $link['route'])) {
                        continue;
                    }

                    if ($section && $section !== $shownSection) {
                        $link['section'] = $shownSection = $section;
                    }

                    $link['label'] = match (true) {
                        $link['label'] === 'Dashboard & Reports' => 'Dashboard',
                        auth()->user()->role === 'maintenance' && $link['route'] === 'admin.maintenance-requests.index' => 'My Tickets',
                        default => $link['label'],
                    };
                    $visible[] = $link;
                }

                $navLinks = $visible;
            }
        @endphp

        <div class="min-h-screen lg:flex">
            <aside class="hidden border-b border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:border-b-0 lg:border-r">
                <div class="flex h-full flex-col">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <a href="{{ route('dashboard') }}" class="brand-mark block">
                            <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House logo" class="brand-logo sidebar-logo w-full object-contain">
                        </a>
                    </div>

                    <nav aria-label="Main navigation" class="sidebar-nav flex min-h-0 flex-1 flex-col gap-0.5 overflow-y-auto px-3 py-3">
                        @foreach ($navLinks as $link)
                            @isset($link['section'])
                                <p class="sidebar-nav__section">{{ $link['section'] }}</p>
                            @endisset
                            @php $isActive = request()->routeIs(...(array) $link['match']); @endphp
                            <a href="{{ route($link['route']) }}"
                                @if ($isActive) aria-current="page" @endif
                                class="sidebar-nav__link whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ $isActive ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                {{ svg($link['icon'], 'sidebar-nav__icon', ['aria-hidden' => 'true']) }}
                                <span class="truncate">{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>

                    <div class="border-t border-slate-200 px-4 py-3">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('profile.edit') }}" class="profile-link flex min-w-0 flex-1 items-center gap-3 rounded-md p-1" title="Edit my profile">
                                @include('partials.avatar', ['user' => auth()->user()])
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                                    <span class="block text-xs uppercase tracking-wide text-slate-500">{{ auth()->user()->roleLabel() }}</span>
                                </span>
                            </a>
                            <button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch color theme">
                                @include('partials.theme-icons')
                            </button>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="mt-2">
                            @csrf
                            <button class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <header class="mobile-topbar sticky top-0 z-40 border-b border-slate-200 px-4 py-3 lg:hidden" data-mobile-menu>
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                        <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House" class="brand-logo h-9 w-auto max-w-32 object-contain">
                        <p class="truncate border-l border-slate-200 pl-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ auth()->user()->roleLabel() }}</p>
                    </a>
                    <div class="flex items-center gap-2">
                        @include('partials.notifications')
                        <button type="button" class="theme-toggle theme-toggle--compact" data-theme-toggle aria-label="Switch color theme">
                            @include('partials.theme-icons')
                        </button>
                        <button type="button"
                            class="mobile-menu-toggle grid size-11 shrink-0 place-items-center rounded-md border border-slate-300"
                        aria-label="Open navigation menu"
                        aria-expanded="false"
                        aria-controls="mobile-navigation"
                        data-mobile-menu-toggle>
                        <svg class="mobile-menu-open-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                        <svg class="mobile-menu-close-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18"/>
                        </svg>
                        </button>
                    </div>
                </div>

                <div id="mobile-navigation" class="mobile-menu-panel" data-mobile-menu-panel hidden>
                    <nav aria-label="Mobile navigation" class="grid gap-1 p-2">
                        @foreach ($navLinks as $link)
                            @isset($link['section'])
                                <p class="sidebar-nav__section">{{ $link['section'] }}</p>
                            @endisset
                            @php $isActive = request()->routeIs(...(array) $link['match']); @endphp
                            <a href="{{ route($link['route']) }}"
                                class="mobile-menu-link {{ $isActive ? 'is-active' : '' }}"
                                @if ($isActive) aria-current="page" @endif>
                                <span class="flex min-w-0 items-center gap-3">
                                    {{ svg($link['icon'], 'sidebar-nav__icon', ['aria-hidden' => 'true']) }}
                                    <span class="truncate">{{ $link['label'] }}</span>
                                </span>
                                @if ($isActive)
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Current</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                    <div class="mobile-menu-account flex items-center justify-between gap-3 border-t border-slate-200 p-3">
                        <a href="{{ route('profile.edit') }}" class="profile-link flex min-w-0 items-center gap-3 rounded-md p-1">
                            @include('partials.avatar', ['user' => auth()->user(), 'size' => 'h-9 w-9 text-xs'])
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                                <span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500">My profile</span>
                            </span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="min-w-0 flex-1 lg:ml-72">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 @yield('content-shell-class')">
                    @hasSection('immersive-dashboard')
                    @else
                    <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:mb-6">
                        <div>
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                {{ $isAdmin ? (auth()->user()->isAdmin() ? 'Admin workspace' : auth()->user()->roleLabel().' workspace') : 'Tenant portal' }}
                            </p>
                            <h1 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">@yield('page-title', 'Dashboard')</h1>
                            @hasSection('page-subtitle')
                                <p class="mt-1 text-sm text-slate-600">@yield('page-subtitle')</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="hidden lg:block">@include('partials.notifications')</div>
                            @yield('page-actions')
                        </div>
                    </header>
                    @endif

                    <div class="mb-5 space-y-3">
                        @include('partials.flash')
                    </div>

                    @yield('content')
                </div>
            </main>

        </div>
    @else
        <button type="button" class="theme-toggle theme-toggle--floating" data-theme-toggle aria-label="Switch color theme">
            @include('partials.theme-icons')
        </button>
        <main>
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @include('partials.flash')
                @yield('content')
            </div>
        </main>
    @endauth
</body>
</html>
