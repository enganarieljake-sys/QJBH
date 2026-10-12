@extends('layouts.app')

@section('title', 'Add Tenant')
@section('page-title', 'Add Tenant')

@section('content')
    <form method="POST" action="{{ route('admin.tenants.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.tenants._form')
    </form>
@endsection
