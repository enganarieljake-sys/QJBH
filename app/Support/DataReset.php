<?php

namespace App\Support;

use App\Models\PaymentSubmission;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Wipes all tenant and money records so the boarding house can start fresh.
 * Keeps admin accounts, rooms, payment QR channels, settings, house rules,
 * and the landing page.
 */
class DataReset
{
    /**
     * @return array<string, int> rows removed per table
     */
    public static function run(): array
    {
        $files = PaymentSubmission::get(['receipt_disk', 'receipt_path'])
            ->map(fn ($submission) => [$submission->receipt_disk, $submission->receipt_path])
            ->merge(MaintenanceRequest::get(['tools_taken_disk', 'tools_taken_photo', 'tools_returned_disk', 'tools_returned_photo'])
                ->flatMap(fn ($ticket) => [
                    [$ticket->tools_taken_disk, $ticket->tools_taken_photo],
                    [$ticket->tools_returned_disk, $ticket->tools_returned_photo],
                ]))
            ->merge(User::where('role', 'tenant')->whereNotNull('avatar_path')->get(['avatar_disk', 'avatar_path'])
                ->map(fn ($user) => [$user->avatar_disk, $user->avatar_path]));

        $counts = DB::transaction(function () {
            $tenantUserIds = User::where('role', 'tenant')->pluck('id');
            $counts = [];

            foreach ([
                'payment_submissions', 'payments', 'bills', 'meter_readings', 'checklist_reports', 'maintenance_requests',
                'concerns', 'visitors', 'tenants', 'announcements', 'expenses', 'notifications', 'password_reset_tokens',
            ] as $table) {
                $counts[$table] = DB::table($table)->delete();
            }

            DB::table('security_codes')->whereIn('user_id', $tenantUserIds)->delete();
            $counts['tenant_accounts'] = User::whereIn('id', $tenantUserIds)->delete();

            Room::where('status', 'occupied')->update(['status' => 'available']);

            return $counts;
        });

        // Files go last so a failed wipe never leaves records without images.
        foreach ($files as [$disk, $path]) {
            PaymentFiles::delete($disk, $path);
        }

        return $counts;
    }
}
