@extends('layouts.app')

@section('title', 'Edit Announcement')
@section('page-title', 'Edit Announcement')

@section('content')
    @include('admin.announcements._form', [
        'action' => route('admin.announcements.update', $announcement),
        'method' => 'PUT',
        'submitLabel' => 'Save Changes',
    ])
@endsection
