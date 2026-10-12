<?php

namespace App\Console\Commands;

use App\Support\OperationalBackup;
use Illuminate\Console\Command;

class VerifyOperationsBackup extends Command
{
    protected $signature = 'operations:backup-verify {path? : Backup object path; defaults to the latest database backup}';
    protected $description = 'Verify the checksum and manifest of an operational database backup';

    public function handle(OperationalBackup $backup): int
    {
        $path = $this->argument('path') ?: $backup->latestDatabaseBackup();
        if (! $path) {
            $this->error('No database backup was found.');
            return self::FAILURE;
        }

        $result = $backup->verify($path);
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result['valid'] ? self::SUCCESS : self::FAILURE;
    }
}
