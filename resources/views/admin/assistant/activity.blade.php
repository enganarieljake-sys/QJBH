@extends('layouts.app')

@section('title', 'QJ Bot Activity')
@section('page-title', 'QJ Bot Activity')
@section('page-subtitle', 'Everything QJ Bot prepared, who confirmed it, and what happened.')
@section('page-actions')
    <a href="{{ route('admin.assistant.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Back to chat</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Admin</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Details</th>
                    <th class="px-4 py-3">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($actions as $action)
                    @php $card = $action->card(); @endphp
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3">{{ $action->created_at->format('M j, g:i A') }}</td>
                        <td class="px-4 py-3">{{ $action->user?->name }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $action->title }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @foreach ($card['lines'] as [$label, $value])
                                <span class="block"><span class="text-slate-500">{{ $label }}:</span> {{ $value }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="action-status action-status--{{ $card['status'] }}">{{ ucfirst($card['status']) }}</span>
                            @if ($action->result_message)
                                <span class="mt-1 block text-xs text-slate-500">{{ $action->result_message }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">QJ Bot has not done anything yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $actions->links() }}</div>
@endsection
