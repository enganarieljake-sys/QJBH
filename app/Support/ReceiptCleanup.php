<?php

namespace App\Support;

use App\Models\PaymentSubmission;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Daily job: deletes receipt images from reviewed submissions older than the
 * months the admin chose to keep, so storage does not fill up. The payment
 * record and official receipt stay. Pending receipts are never touched.
 */
class ReceiptCleanup
{
    /**
     * Receipts uploaded before this date are deleted, or null when the admin
     * keeps receipts forever (0 months). Keeping 3 months in December keeps
     * October, November, and December.
     */
    public static function cutoff(): ?Carbon
    {
        $months = (int) Setting::get('receipt_keep_months');

        return $months > 0 ? now()->startOfMonth()->subMonths($months - 1) : null;
    }

    public static function run(): int
    {
        $cutoff = self::cutoff();

        if (! $cutoff) {
            return 0;
        }

        $deleted = 0;

        PaymentSubmission::where('status', '!=', 'pending')
            ->whereNull('receipt_purged_at')
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($submissions) use (&$deleted) {
                foreach ($submissions as $submission) {
                    PaymentFiles::delete($submission->receipt_disk, $submission->receipt_path);
                    $submission->forceFill(['receipt_purged_at' => now()])->save();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
