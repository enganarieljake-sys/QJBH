@extends('layouts.app')

@section('title', request('group') === 'staff' ? 'Staff Profiles' : 'User Authentication & Roles')
@section('page-title', request('group') === 'staff' ? 'Staff Profiles' : 'User Authentication & Roles')
@section('page-actions')
    @if (request('group') === 'staff')
        <a href="{{ route('admin.tenants.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">View Tenants</a>
    @endif
    <a href="{{ route('admin.users.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add User</a>
@endsection

@section('content')
    @include('partials.search-bar', ['placeholder' => 'Search name, email, or role…', 'keep' => request('group') ? ['group' => request('group')] : []])
    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Approval</th>
                    <th class="px-4 py-3">Tenant Record</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @include('partials.avatar', ['user' => $user, 'size' => 'h-11 w-11 text-sm'])
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="mt-0.5 flex items-center gap-1.5 text-xs {{ $user->isOnline() ? 'font-semibold text-emerald-600' : 'text-slate-500' }}">
                                        <span class="size-2 rounded-full {{ $user->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $user->presenceLabel() }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->roleLabel() }}</td>
                        <td class="px-4 py-3">
                            @if ($user->isTenant())
                                @if ($user->isApprovedTenant())
                                    <span class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold">Approved</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.approve-tenant', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">Approve Tenant</button>
                                    </form>
                                @endif
                            @else
                                <span class="text-xs text-slate-500">Staff</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->tenant?->full_name ?? 'Not linked' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.users.show', $user) }}" class="font-medium text-slate-800 hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
