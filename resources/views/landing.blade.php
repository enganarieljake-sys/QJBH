@extends('layouts.app')

@section('title', 'Boarding House Management System')

@section('content')
    @include('partials.scenic-bg')
    <section class="grid min-h-[80vh] items-center gap-10">
        <div>
            <div class="mb-8 inline-flex">
                <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House logo" class="landing-logo object-contain">
            </div>
            <h1 class="mt-3 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                {{ $headline }}
            </h1>
            <p class="mt-5 max-w-2xl whitespace-pre-line text-base leading-7 text-slate-600">{{ $intro }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="rounded-md bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700">Login</a>
                <a href="{{ route('register') }}" class="rounded-md border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-white">Apply as Tenant</a>
                <a href="{{ route('house-rules') }}" class="rounded-md border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-white">House rules</a>
                @if ($vacantRooms->isNotEmpty())
                    <a href="#available-rooms" class="rounded-md border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-white">See available rooms</a>
                @endif
            </div>
        </div>

    </section>

    @if ($gallery->isNotEmpty())
        <section class="pb-16" aria-labelledby="gallery-heading">
            <h2 id="gallery-heading" class="text-2xl font-bold text-slate-950">Inside Queen Jackie</h2>
            <div class="landing-gallery mt-6">
                @foreach ($gallery as $photo)
                    <figure class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                        <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?: 'Photo of Queen Jackie Boarding House' }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                        @if ($photo->caption)
                            <figcaption class="px-4 py-3 text-sm text-slate-700">{{ $photo->caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @if ($showRooms)
    <section id="available-rooms" class="pb-16" aria-labelledby="available-rooms-heading">
        <h2 id="available-rooms-heading" class="text-2xl font-bold text-slate-950">Available rooms</h2>
        <p class="mt-1 text-sm text-slate-600">Pick a room and send your application. Ate Jackie will confirm it with you.</p>

        @if ($vacantRooms->isEmpty())
            <p class="mt-6 rounded-lg bg-white p-6 text-sm text-slate-600 shadow-sm ring-1 ring-slate-200">All rooms are full right now. You can still apply and we will contact you when a slot opens.</p>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vacantRooms as $room)
                    <article class="room-card overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                        @if ($room->photo)
                            <img src="{{ $room->photoUrl() }}" alt="Room {{ $room->room_number }}" loading="lazy" class="aspect-video w-full object-cover">
                        @else
                            <div class="room-card__placeholder aspect-video w-full" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-7h6v7"/></svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-950">Room {{ $room->room_number }}</h3>
                                    <p class="text-sm capitalize text-slate-600">{{ $room->type }} · Floor {{ $room->floor }}</p>
                                </div>
                                <span class="room-card__slots">{{ $room->slots_left }} {{ Str::plural('slot', $room->slots_left) }} left</span>
                            </div>
                            <p class="mt-4 text-2xl font-bold text-slate-950">PHP {{ number_format($room->monthly_rate, 0) }}<span class="text-sm font-medium text-slate-500"> / month</span></p>
                            <a href="{{ route('register', ['room' => $room->id]) }}" class="mt-4 block rounded-md bg-slate-900 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-slate-700">Apply for this room</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    @endif
@endsection
