@extends('layouts.app')

@section('title', 'Apply as Tenant - Boarding House Management System')

@section('content')
    @include('partials.scenic-bg')
    @include('partials.home-link')
    <div class="mx-auto mt-12 max-w-md rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-2xl font-bold text-slate-950">Apply as tenant</h1>
        <p class="mt-1 text-sm text-slate-600">Send your application. The admin will review it before your tenant portal opens.</p>

        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="preferred_room_id">Room you want</label>
                <select id="preferred_room_id" name="preferred_room_id"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    <option value="">No preference</option>
                    @foreach ($vacantRooms as $room)
                        <option value="{{ $room->id }}" @selected((string) old('preferred_room_id', request('room')) === (string) $room->id)>
                            Room {{ $room->room_number }} · {{ ucfirst($room->type) }} · PHP {{ number_format($room->monthly_rate, 0) }}/mo · {{ $room->slots_left }} left
                        </option>
                    @endforeach
                </select>
                @error('preferred_room_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            </div>

            <div>
                <p class="block text-sm font-medium text-slate-700">House rules</p>
                <div class="house-rules-box mt-1 max-h-48 overflow-y-auto whitespace-pre-line rounded-md border border-slate-300 px-3 py-2 text-xs leading-6" tabindex="0" aria-label="House rules">{{ $houseRules }}</div>
                <label class="mt-2 flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="accept_rules" value="1" required @checked(old('accept_rules')) class="mt-1 rounded border-slate-300">
                    <span>I have read and agree to follow the house rules.</span>
                </label>
                @error('accept_rules') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                Submit application
            </button>
        </form>

        <p class="mt-5 text-sm text-slate-600">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-slate-950 hover:underline">Login</a>
        </p>
    </div>
@endsection
