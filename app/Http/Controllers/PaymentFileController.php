<?php

namespace App\Http\Controllers;

use App\Models\PaymentChannel;
use App\Models\PaymentSubmission;
use App\Support\PaymentFiles;
use App\Support\StaffAccess;
use Illuminate\Http\Request;

/**
 * Serves payment images from private storage so receipts are only visible
 * to admins and the tenant who uploaded them.
 */
class PaymentFileController extends Controller
{
    public function qr(Request $request, PaymentChannel $paymentChannel)
    {
        $downloadName = null;

        if ($request->boolean('download')) {
            $extension = pathinfo((string) $paymentChannel->qr_path, PATHINFO_EXTENSION);
            $downloadName = str($paymentChannel->name)->slug().'-qr.'.$extension;
        }

        return PaymentFiles::response($paymentChannel->qr_disk, $paymentChannel->qr_path, $downloadName);
    }

    public function receipt(Request $request, PaymentSubmission $paymentSubmission)
    {
        $user = $request->user();
        $ownsReceipt = $user->tenant && (int) $user->tenant->id === (int) $paymentSubmission->tenant_id;

        abort_unless(StaffAccess::allows($user, 'admin.payment-submissions.show') || $ownsReceipt, 403);
        abort_unless($paymentSubmission->hasReceiptImage(), 410, 'This receipt image was deleted to save storage. The payment record is kept.');

        return PaymentFiles::response($paymentSubmission->receipt_disk, $paymentSubmission->receipt_path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
