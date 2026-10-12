@extends('layouts.app')

@section('title', 'Set Up Your Account - Boarding House Management System')

@section('content')
    <div class="mx-auto mt-12 max-w-md rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House logo" class="brand-logo h-16 max-w-40 object-contain">
        </div>

        <h1 class="text-2xl font-bold text-slate-950">Set up your account</h1>
        <p class="mt-1 text-sm text-slate-600">Choose a password for your account. You'll use it with your email to log in.</p>

        <form method="POST" action="{{ route('invitation.store') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required readonly autocomplete="username"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="password">New password</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" autofocus
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">At least 8 characters.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>

            <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                Set password &amp; continue
            </button>
        </form>
    </div>
@endsection
