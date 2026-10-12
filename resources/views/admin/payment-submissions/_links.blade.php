@php $pendingReceipts = \App\Models\PaymentSubmission::where('status', 'pending')->count(); @endphp
<a href="{{ route('admin.payment-submissions.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">
    Receipts @if ($pendingReceipts)<span class="ml-1 rounded-full bg-rose-600 px-2 py-0.5 text-xs text-white">{{ $pendingReceipts }}</span>@endif
</a>
@staffcan('admin.payment-channels.index')
<a href="{{ route('admin.payment-channels.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Payment QR</a>
@endstaffcan
