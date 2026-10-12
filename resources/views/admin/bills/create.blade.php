@extends('layouts.app')

@section('title', 'Create Bill')
@section('page-title', 'Create Bill')

@section('content')
    <form method="POST" action="{{ route('admin.bills.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.bills._form')
    </form>
@endsection
