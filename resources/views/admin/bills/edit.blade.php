@extends('layouts.app')

@section('title', 'Edit Bill')
@section('page-title', 'Edit Bill')

@section('content')
    <form method="POST" action="{{ route('admin.bills.update', $bill) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.bills._form')
    </form>
@endsection
