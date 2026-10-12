@extends('layouts.app')

@section('title', 'Add Room')
@section('page-title', 'Add Room')

@section('content')
    <form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.rooms._form')
    </form>
@endsection
