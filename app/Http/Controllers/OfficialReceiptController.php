<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Support\ReceiptQr;
use App\Support\StaffAccess;
use Illuminate\Http\Request;

/**
 * Printable official receipt for a recorded payment. Tenants can open only
 * their own; admins can open any.
 */
class OfficialReceiptController extends Controller
{
    public function show(Request $request, Payment $payment)
    {
        $user = $request->user();
        abort_unless(self::canView($user, $payment), 403);

        $payment->load(['tenant', 'bill.room', 'bill.payments']);

        return view('receipts.official', [
            'payment' => $payment,
            'qr' => ReceiptQr::svg($payment),
            'backUrl' => $user->isStaff() ? route('admin.payments.show', $payment) : route('tenant.payments.index'),
        ]);
    }

    /**
     * Where a scanned receipt QR lands. The tenant who owns it (or staff) gets
     * the full receipt to view or download again; anyone else only sees that
     * it is a genuine receipt, without the tenant's details.
     */
    public function verify(Request $request, Payment $payment)
    {
        if ($request->user() && self::canView($request->user(), $payment)) {
            return redirect()->route('payments.official-receipt', $payment);
        }

        // After logging in from here, come back to this receipt.
        if (! $request->user()) {
            redirect()->setIntendedUrl($request->fullUrl());
        }

        // Initials only ("J. E."), so a stranger scanning it learns nothing personal.
        $initials = collect(preg_split('/\s+/', trim((string) $payment->tenant?->full_name)))
            ->filter()
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)).'.')
            ->implode(' ');

        return view('receipts.verify', ['payment' => $payment, 'initials' => $initials ?: 'Tenant']);
    }

    private static function canView(User $user, Payment $payment): bool
    {
        $ownsPayment = $user->tenant && (int) $user->tenant->id === (int) $payment->tenant_id;

        return StaffAccess::allows($user, 'admin.payments.show') || ($user->isApprovedTenant() && $ownsPayment);
    }
}
