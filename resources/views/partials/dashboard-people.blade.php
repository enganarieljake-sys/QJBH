@php $canAddUser = \App\Support\StaffAccess::allows(auth()->user(), 'admin.users.create'); @endphp

<section class="dashboard-people" aria-labelledby="dashboard-people-title">
    <div class="tenant-section-heading dashboard-people__heading">
        <div>
            <p>Live presence</p>
            <h2 id="dashboard-people-title">Who’s around</h2>
        </div>
        <span>{{ $dashboardPeople->filter->isOnline()->count() }} online</span>
    </div>
    <div class="dashboard-people__rail" role="list" aria-label="Users and their current status">
        @if ($canAddUser)
            <a href="{{ route('admin.users.create') }}" class="dashboard-person dashboard-person--add" role="listitem" aria-label="Add user">
                <span class="dashboard-person__add-icon" aria-hidden="true">{{ svg('solar-outline-add-circle') }}</span>
                <b>Add</b>
                <small>New user</small>
            </a>
        @endif
        @foreach ($dashboardPeople as $person)
            <div class="dashboard-person" role="listitem" title="{{ $person->name }} · {{ $person->roleLabel() }} · {{ $person->presenceLabel() }}">
                <span class="dashboard-person__avatar">
                    @include('partials.avatar', ['user' => $person, 'size' => 'h-14 w-14 text-sm'])
                    <i class="{{ $person->isOnline() ? 'is-online' : '' }}" aria-hidden="true"></i>
                </span>
                <b>{{ $person->firstName() }}</b>
                <small class="{{ $person->isOnline() ? 'is-online' : '' }}">{{ $person->presenceShortLabel() }}</small>
            </div>
        @endforeach
    </div>
</section>
