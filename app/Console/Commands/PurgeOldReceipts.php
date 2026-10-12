<?php

namespace App\Console\Commands;

use App\Support\ReceiptCleanup;
use Illuminate\Console\Command;

class PurgeOldReceipts extends Command
{
    protected $signature = 'receipts:purge-old';

    protected $description = 'Delete receipt images older than the months set in Settings, keeping the payment records';

    public function handle(): int
    {
        $this->info('Deleted '.ReceiptCleanup::run().' old receipt images.');

        return self::SUCCESS;
    }
}
