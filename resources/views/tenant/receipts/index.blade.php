@extends('layouts.app')

@section('title', 'My Receipts')
@section('page-title', 'My Receipts')
@section('page-subtitle', 'Receipts you sent and whether the admin has confirmed them.')
@section('page-actions')
    @if ($tenant)
        @include('partials.qr-scanner')
        <a href="{{ route('tenant.receipts.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Pay &amp; Send Receipt</a>
    @endif
@endsection

@section('content')
    @if (! $tenant)
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-600">Your tenant account is not active yet. Once the admin approves it, you can send payment receipts here.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Sent</th>
                        <th class="px-4 py-3">Bill</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($submissions as $submission)
                        <tr>
                            <td class="px-4 py-3">{{ $submission->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $submission->bill?->billing_month?->format('F Y') }}</td>
                            <td class="px-4 py-3">PHP {{ number_format($submission->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $submission->reference_number ?: 'None' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold">{{ ['pending' => 'Waiting for admin', 'approved' => 'Confirmed', 'rejected' => 'Not accepted'][$submission->status] }}</span>
                                @if ($submission->rejection_reason)
                                    <span class="block text-xs text-slate-500">{{ $submission->rejection_reason }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($submission->hasReceiptImage())
                                    <a href="{{ route('receipts.image', $submission) }}" target="_blank" rel="noopener" class="font-medium text-slate-800 hover:underline">View</a>
                                @else
                                    <span class="text-xs text-slate-500" title="Old receipt images are deleted to save storage">Image removed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No receipts sent yet. After you pay, send your screenshot here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $submissions->links() }}</div>
    @endif
@endsection
