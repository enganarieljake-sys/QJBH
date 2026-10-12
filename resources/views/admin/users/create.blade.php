@extends('layouts.app')

@section('title', 'Add User')
@section('page-title', 'Add User')

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.users._form')
    </form>
@endsection
