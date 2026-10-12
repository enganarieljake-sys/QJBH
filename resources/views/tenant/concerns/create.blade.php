@extends('layouts.app')

@section('title', 'Send a Concern')
@section('page-title', 'Send a Concern')
@section('page-subtitle', 'Only the admin can read this.')

@section('content')
    <form method="POST" action="{{ route('tenant.concerns.store') }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="category" class="block text-sm font-medium text-slate-700">Category</label>
                <select id="category" name="category" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700">Subject</label>
                <input id="subject" name="subject" required maxlength="150" value="{{ old('subject') }}" placeholder="e.g. Loud music late at night" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('subject') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="message" class="block text-sm font-medium text-slate-700">Details</label>
                <textarea id="message" name="message" rows="6" required maxlength="3000" placeholder="What happened, when, and where?" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
                @error('message') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Send Concern</button>
            <a href="{{ route('tenant.concerns.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
@endsection
