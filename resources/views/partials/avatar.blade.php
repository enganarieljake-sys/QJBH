@php($avatarSize = $size ?? 'h-10 w-10 text-sm')
<img src="{{ $user->avatar_path ? $user->avatarUrl() : asset('images/default-avatar.jpg') }}"
    alt="{{ $user->name }}"
    class="user-avatar {{ $avatarSize }}"
    {{ isset($preview) ? 'data-avatar-preview' : '' }}>
