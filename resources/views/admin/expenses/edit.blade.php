@extends('layouts.app')

@section('title', 'Edit Expense')
@section('page-title', 'Edit Expense')
@section('page-actions')
    <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
        @csrf
        @method('DELETE')
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
    </form>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @method('PUT')
        @include('admin.expenses._form')
    </form>
@endsection
