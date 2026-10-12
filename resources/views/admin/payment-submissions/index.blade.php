@extends('layouts.app')

@section('title', 'Payment Receipts')
@section('page-title', 'Payment Receipts')
@section('page-subtitle', 'Check each receipt against your GCash or bank history, then approve it to record the payment.')
@section('page-actions')
    @staffcan('admin.payment-channels.index')
    <a href="{{ route('admin.payment-channels.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Payment QR</a>
    @endstaffcan
    <a href="{{ route('admin.payments.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Payment Records</a>
@endsection

@section('content')
    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filter receipts by status">
        @foreach (\App\Models\PaymentSubmission::STATUSES as $option)
            <a href="{{ route('admin.payment-submissions.index', ['status' => $option]) }}"
                @if ($status === $option) aria-current="page" @endif
                class="rounded-md px-3 py-2 text-sm font-semibold capitalize {{ $status === $option ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:bg-white' }}">
                {{ $option }} ({{ $counts[$option] ?? 0 }})
            </a>
        @endforeach
    </nav>
    @include('partials.search-bar', ['placeholder' => 'Search tenant or reference number…', 'keep' => ['status' => $status]])

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Sent</th>
                    <th class="px-4 py-3">Tenant</th>
                    <th class="px-4 py-3">Bill</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Method</th>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($submissions as $submission)
                    <tr>
                        <td class="px-4 py-3">{{ $submission->created_at->format('Y-m-d g:i A') }}</td>
                        <td class="px-4 py-3">{{ $submission->tenant?->full_name }}</td>
                        <td class="px-4 py-3">{{ $submission->bill?->billing_month?->format('F Y') }}</td>
                        <td class="px-4 py-3">PHP {{ number_format($submission->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ $submission->channel?->name ?? 'Not specified' }}</td>
                        <td class="px-4 py-3">{{ $submission->reference_number ?: 'None' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.payment-submissions.show', $submission) }}" class="font-medium text-slate-800 hover:underline">{{ $submission->isPending() ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No {{ $status }} receipts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $submissions->links() }}</div>
@endsection
