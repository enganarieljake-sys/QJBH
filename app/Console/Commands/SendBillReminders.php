<?php

namespace App\Console\Commands;

use App\Support\BillReminders;
use Illuminate\Console\Command;

class SendBillReminders extends Command
{
    protected $signature = 'bills:send-reminders';

    protected $description = 'Mark late bills overdue and remind tenants about due and overdue bills';

    public function handle(): int
    {
        $result = BillReminders::run();

        $this->info("Sent {$result['due_soon']} due-soon and {$result['overdue']} overdue reminders.");

        return self::SUCCESS;
    }
}
