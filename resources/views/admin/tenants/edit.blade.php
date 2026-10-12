@extends('layouts.app')

@section('title', 'Edit Tenant')
@section('page-title', 'Edit '.$tenant->full_name)

@section('content')
    <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.tenants._form')
    </form>
@endsection
