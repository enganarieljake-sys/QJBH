@extends('layouts.app')

@section('title', 'Create Maintenance Request')
@section('page-title', 'Create Maintenance Request')

@section('content')
    <form method="POST" action="{{ route('admin.maintenance-requests.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.maintenance-requests._form')
    </form>
@endsection
