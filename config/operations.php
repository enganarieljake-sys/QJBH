<?php

return [
    /*
    | Operational backups are written to object storage, never to Vercel's
    | temporary filesystem. A second S3-compatible disk can be configured
    | later with BACKUP_* variables for an off-site copy.
    */
    'backup' => [
        'enabled' => env('OPERATIONS_BACKUP_ENABLED', true),
        'disk' => env('BACKUP_FILES_DISK', env('PAYMENT_FILES_DISK', 'local')),
        'source_disk' => env('PAYMENT_FILES_DISK', 'local'),
        'prefix' => trim(env('BACKUP_PREFIX', '_operations-backups'), '/'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 35),
        'storage_snapshot_day' => (int) env('BACKUP_STORAGE_SNAPSHOT_DAY', 0), // Sunday
    ],
];
