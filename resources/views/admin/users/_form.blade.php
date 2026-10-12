@csrf
@if ($user->exists)
    @method('PUT')
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Name</label>
        <input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Role</label>
        <select name="role" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach (\App\Models\User::ROLES as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ \App\Support\StaffAccess::LABELS[$role] }}</option>
            @endforeach
        </select>
    </div>
    <div></div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Password</label>
        <input name="password" type="password" @required(! $user->exists) class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @if ($user->exists)
            <p class="mt-1 text-xs text-slate-500">Leave blank to keep the current password.</p>
        @endif
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Confirm password</label>
        <input name="password_confirmation" type="password" @required(! $user->exists) class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save User</button>
    <a href="{{ route('admin.users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
