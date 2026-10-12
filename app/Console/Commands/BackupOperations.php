<?php

namespace App\Console\Commands;

use App\Support\OperationalBackup;
use Illuminate\Console\Command;

class BackupOperations extends Command
{
    protected $signature = 'operations:backup {--with-files : Snapshot uploaded files as well as the database}';
    protected $description = 'Create and verify an operational backup in durable object storage';

    public function handle(OperationalBackup $backup): int
    {
        $result = $backup->run((bool) $this->option('with-files'));

        $this->info('Backup created and checksum verified.');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
