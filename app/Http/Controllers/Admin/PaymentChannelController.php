<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use App\Support\PaymentFiles;
use Illuminate\Http\Request;

class PaymentChannelController extends Controller
{
    public function index()
    {
        $channels = PaymentChannel::withCount(['submissions as pending_count' => fn ($query) => $query->where('status', 'pending')])
            ->latest()
            ->get();

        return view('admin.payment-channels.index', compact('channels'));
    }

    public function create()
    {
        return view('admin.payment-channels.create', ['channel' => new PaymentChannel(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, qrRequired: true);
        ['disk' => $data['qr_disk'], 'path' => $data['qr_path']] = PaymentFiles::store($request->file('qr'), 'payment-qr');

        PaymentChannel::create($data);

        return redirect()->route('admin.payment-channels.index')->with('success', 'Payment QR posted. Tenants can now see it when they pay.');
    }

    public function edit(PaymentChannel $paymentChannel)
    {
        return view('admin.payment-channels.edit', ['channel' => $paymentChannel]);
    }

    public function update(Request $request, PaymentChannel $paymentChannel)
    {
        $data = $this->validated($request, qrRequired: false);

        $old = ['disk' => $paymentChannel->qr_disk, 'path' => $paymentChannel->qr_path];

        if ($request->hasFile('qr')) {
            ['disk' => $data['qr_disk'], 'path' => $data['qr_path']] = PaymentFiles::store($request->file('qr'), 'payment-qr');
        }

        $paymentChannel->update($data);

        if ($request->hasFile('qr')) {
            PaymentFiles::delete($old['disk'], $old['path']);
        }

        return redirect()->route('admin.payment-channels.index')->with('success', 'Payment QR updated.');
    }

    public function destroy(PaymentChannel $paymentChannel)
    {
        $paymentChannel->delete();
        PaymentFiles::delete($paymentChannel->qr_disk, $paymentChannel->qr_path);

        return redirect()->route('admin.payment-channels.index')->with('success', 'Payment QR removed.');
    }

    private function validated(Request $request, bool $qrRequired): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:60'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'qr' => [$qrRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'qr.required' => 'Please upload the QR code image.',
        ]);

        unset($data['qr']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
