@extends('layouts.app')

@section('title', 'Post Payment QR')
@section('page-title', 'Post Payment QR')

@section('content')
    @include('admin.payment-channels._form', [
        'action' => route('admin.payment-channels.store'),
        'method' => 'POST',
        'submitLabel' => 'Post QR Code',
    ])
@endsection
