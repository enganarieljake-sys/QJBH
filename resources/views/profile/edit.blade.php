@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', 'Update your name, profile photo, and personal app color.')

@section('content')
    <section class="mb-6 max-w-xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200" data-push-settings
            data-config-url="{{ route('push.config') }}"
            data-subscribe-url="{{ route('push.subscriptions.store') }}"
            data-unsubscribe-url="{{ route('push.subscriptions.destroy') }}"
            data-test-url="{{ route('push.test') }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-slate-950">Phone notifications</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600" data-push-message>Get announcements and important account or work updates even when QJBH is closed.</p>
                </div>
                <span class="push-status" data-push-status>Checking…</span>
            </div>
            <button type="button" class="mt-5 w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60" data-push-toggle disabled>Enable notifications</button>
              <button type="button" class="mt-3 hidden w-full rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60" data-push-test>Send test notification</button>
            <p class="mt-3 hidden rounded-md bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800" data-ios-help>On iPhone or iPad, open this site from the QJBH icon on your Home Screen. In Safari, tap Share, then Add to Home Screen.</p>
    </section>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @method('PUT')

        <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
            <label for="avatar" class="profile-avatar-frame" title="Change profile photo">
                @include('partials.avatar', ['user' => $user, 'size' => 'h-28 w-28 text-3xl', 'preview' => true])
                <span class="profile-avatar-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </span>
                <span class="sr-only">Change profile photo</span>
            </label>
            <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" data-avatar-input class="sr-only">
            <div class="text-center sm:text-left">
                <p class="text-lg font-semibold text-slate-950">{{ $user->name }}</p>
                <p class="text-sm text-slate-600">{{ $user->email }}</p>
                <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ $user->role }}</p>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <p class="text-xs text-slate-500">Tap your photo to change it. PNG, JPG, or WEBP up to 2 MB.</p>
            @error('avatar')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror

            @if ($user->avatar_path)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300">
                    Remove current photo
                </label>
            @endif

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Display name</label>
                <input id="name" name="name" required maxlength="255" autocomplete="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('name')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @php
            $dashboardColors = [
                'orange' => ['Orange', '#f97316'], 'yellow' => ['Yellow', '#eab308'],
                'blue' => ['Blue', '#0879ed'], 'gold' => ['Gold', '#c99518'],
                'black' => ['Black', '#111827'], 'green' => ['Green', '#16a34a'],
                'red' => ['Red', '#dc2626'], 'purple' => ['Purple', '#9333ea'],
            ];
            $selectedDashboardColor = old('dashboard_color', $user->dashboardColor());
        @endphp
        <section class="mt-6 border-t border-slate-200 pt-6" aria-labelledby="profile-color-heading">
            <h2 id="profile-color-heading" class="font-semibold text-slate-950">My app color</h2>
            <p class="mt-1 text-sm text-slate-600">This personal palette changes your dashboard, navigation, logo, Quick Actions, links, and controls.</p>
            <div class="dashboard-color-setting mt-4" data-dashboard-color-setting>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2">
                    @foreach ($dashboardColors as $value => [$label, $hex])
                        <label class="dashboard-color-choice">
                            <input type="radio" name="dashboard_color" value="{{ $value }}" data-dashboard-color-input @checked($selectedDashboardColor === $value)>
                            <span class="dashboard-color-choice__swatch" style="--choice-color: {{ $hex }}" aria-hidden="true"></span>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="dashboard-color-preview" data-dashboard-color-preview data-dashboard-color="{{ $selectedDashboardColor }}">
                    <span class="dashboard-color-preview__circle" aria-hidden="true"></span>
                    <div class="dashboard-color-preview__top">
                        <div><small>{{ strtoupper($user->roleLabel()) }} DASHBOARD</small><strong>Good day, {{ $user->firstName() }}.</strong></div>
                        <span class="dashboard-color-preview__avatar">{{ $user->initials() }}<i aria-label="Online"></i></span>
                    </div>
                    <div class="dashboard-color-preview__balance"><small>YOUR DASHBOARD</small><strong>Personal palette</strong></div>
                    <div class="dashboard-color-preview__room"><span>Navigation &amp; icons</span><b>Same color palette</b></div>
                    <p><span data-dashboard-color-name>{{ $dashboardColors[$selectedDashboardColor][0] ?? 'Blue' }}</span> preview</p>
                </div>
            </div>
            @error('dashboard_color') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </section>

        <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Profile</button>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="mt-6 max-w-xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200" aria-labelledby="change-password-heading">
        @csrf
        @method('PUT')
        <h2 id="change-password-heading" class="font-semibold text-slate-950">Change password</h2>
        <p class="mt-1 text-sm text-slate-600">Use at least 8 characters. Don't reuse a password from another site.</p>

        <div class="mt-4 space-y-4">
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('current_password', 'password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="new_password" class="block text-sm font-medium text-slate-700">New password</label>
                <input id="new_password" name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('password', 'password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="new_password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
                <input id="new_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>

        <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Change Password</button>
    </form>

    @if ($user->isAdmin())
        @include('partials.data-reset')
    @endif
@endsection
