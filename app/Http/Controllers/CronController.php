<?php

namespace App\Http\Controllers;

use App\Support\Assistant\OpenRouter;
use App\Support\BillReminders;
use App\Support\OperationalBackup;
use App\Support\ReceiptCleanup;
use Illuminate\Http\Request;

/**
 * Entry point for Vercel Cron, which calls this URL daily with
 * "Authorization: Bearer <CRON_SECRET>".
 */
class CronController extends Controller
{
    public function billReminders(Request $request, OperationalBackup $backup)
    {
        $secret = (string) config('services.cron.secret');

        abort_if($secret === '' || ! hash_equals('Bearer '.$secret, (string) $request->header('Authorization')), 401);

        // Vercel Hobby allows one cron job, so it runs every daily task.
        $result = BillReminders::run() + ['old_receipts_deleted' => ReceiptCleanup::run()];

        // Keep QJ Bot's reserve list of free models fresh.
        if (OpenRouter::configured()) {
            $result['free_ai_models'] = count(OpenRouter::discover(force: true));
        }

        // Database backup runs daily. Uploaded files are snapshotted weekly.
        // Let an exception fail the cron visibly so Vercel records the failure.
        $result['backup'] = $backup->run(
            now()->dayOfWeek === (int) config('operations.backup.storage_snapshot_day', 0)
        );

        return response()->json($result);
    }
}
