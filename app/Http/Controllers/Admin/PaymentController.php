<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $payments = Payment::with(['bill.room', 'tenant'])
            ->when($request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->tenant_id))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['reference_number', 'payment_method', 'tenant.full_name', 'bill.room.room_number']))
            ->latest('payment_date')
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $selectedBill = request('bill_id') ? Bill::with(['tenant', 'room', 'payments'])->find(request('bill_id')) : null;

        return view('admin.payments.create', [
            'payment' => new Payment(['payment_date' => today()]),
            'bills' => Bill::with(['tenant', 'room', 'payments'])->where('status', '!=', 'paid')->latest('billing_month')->get(),
            'selectedBill' => $selectedBill,
            'methods' => Payment::METHODS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        $payment = DB::transaction(function () use ($request) {
            $payment = Payment::create($request->validated());
            $payment->bill->updatePaymentStatus();

            return $payment;
        });

        $payment->load('tenant.user');
        $details = [
            'title' => 'Payment received',
            'message' => ($payment->tenant?->full_name ?? 'A tenant').' paid PHP '.number_format((float) $payment->amount, 2).'.',
            'url' => route('admin.payments.show', $payment),
            'kind' => 'payment',
        ];
        Notification::send(User::staff(['admin', 'caretaker']), new ActivityNotification($details));

        $payment->tenant?->user?->notify(new ActivityNotification([
            'title' => 'Payment recorded',
            'message' => 'Your payment of PHP '.number_format((float) $payment->amount, 2).' has been recorded. Tap to view your official receipt.',
            'url' => route('payments.official-receipt', $payment),
            'kind' => 'payment',
        ]));

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        $payment->load(['bill.room', 'tenant']);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
