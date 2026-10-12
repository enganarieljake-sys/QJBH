@extends('layouts.app')

@section('title', 'Edit Payment QR')
@section('page-title', 'Edit '.$channel->name.' QR')

@section('content')
    @include('admin.payment-channels._form', [
        'action' => route('admin.payment-channels.update', $channel),
        'method' => 'PUT',
        'submitLabel' => 'Save Changes',
    ])
@endsection
