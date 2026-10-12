<div class="tenant-wallet__me">
    <p class="tenant-wallet__date">{{ $dashboardHeader['date'] }}</p>
    <a href="{{ route('profile.edit') }}" class="tenant-wallet__avatar" title="My profile">
        @include('partials.avatar', ['user' => auth()->user(), 'size' => 'h-12 w-12 text-base'])
        <span class="tenant-wallet__presence" title="Online now" aria-label="Online now"></span>
        <span class="sr-only">My profile</span>
    </a>
</div>
