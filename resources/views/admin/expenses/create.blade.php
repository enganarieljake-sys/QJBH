@extends('layouts.app')

@section('title', 'Add Expense')
@section('page-title', 'Add Expense')

@section('content')
    <form method="POST" action="{{ route('admin.expenses.store') }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @include('admin.expenses._form')
    </form>
@endsection
