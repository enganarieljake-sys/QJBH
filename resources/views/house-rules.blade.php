@extends('layouts.app')

@section('title', 'House Rules')
@section('page-title', 'House Rules')
@section('page-subtitle', 'Queen Jackie Boarding House')

@section('content')
    @guest
        @include('partials.scenic-bg')
        @include('partials.home-link')
    @endguest
    <section class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 @guest mt-12 @endguest">
        @guest
            <h1 class="text-2xl font-bold text-slate-950">House Rules</h1>
            <p class="mt-1 text-sm text-slate-600">Please read these before applying.</p>
        @endguest
        <div class="house-rules mt-4 whitespace-pre-line text-sm leading-7">{{ $rules }}</div>
        @auth
            @if (auth()->user()->rules_accepted_at)
                <p class="mt-6 text-xs text-slate-500">You agreed to these rules on {{ auth()->user()->rules_accepted_at->format('F j, Y') }}.</p>
            @endif
        @endauth
        @guest
            <a href="{{ route('register') }}" class="mt-6 inline-block rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Apply as tenant</a>
        @endguest
    </section>
@endsection
