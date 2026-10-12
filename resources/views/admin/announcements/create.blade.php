@extends('layouts.app')

@section('title', 'Post Announcement')
@section('page-title', 'Post Announcement')

@section('content')
    @include('admin.announcements._form', [
        'action' => route('admin.announcements.store'),
        'method' => 'POST',
        'submitLabel' => 'Post & Notify Tenants',
    ])
@endsection
