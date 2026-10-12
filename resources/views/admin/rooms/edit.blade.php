@extends('layouts.app')

@section('title', 'Edit Room')
@section('page-title', 'Edit Room '.$room->room_number)

@section('content')
    <form method="POST" action="{{ route('admin.rooms.update', $room) }}" enctype="multipart/form-data" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @include('admin.rooms._form')
    </form>
@endsection
