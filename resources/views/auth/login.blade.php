@extends('layouts.app')

@section('title', 'Login - Boarding House Management System')

@section('content')
    @include('partials.scenic-bg')
    @include('partials.home-link')
    <div class="mx-auto mt-12 max-w-md rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House logo" class="brand-logo h-32 max-w-72 object-contain">
        </div>

        <h1 class="text-2xl font-bold text-slate-950">Sign in</h1>
        <p class="mt-1 text-sm text-slate-600">Use your owner/admin or tenant account.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Remember me
            </label>

            <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                Login
            </button>
        </form>

        <p class="mt-5 text-sm text-slate-600">
            Want to stay with us?
            <a href="{{ route('register') }}" class="font-semibold text-slate-950 hover:underline">Apply as tenant</a>
        </p>
    </div>
@endsection
