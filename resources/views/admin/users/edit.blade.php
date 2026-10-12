@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit '.$user->name)

@section('content')
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.users._form')
    </form>
@endsection
