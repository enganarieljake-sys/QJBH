<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OperationalBackup
{
    public const FORMAT_VERSION = 1;

    public function run(bool $withFiles = false): array
    {
        if (! config('operations.backup.enabled')) {
            throw new RuntimeException('Operational backups are disabled.');
        }

        $started = microtime(true);
        $database = $this->backupDatabase();
        $verification = $this->verify($database['backup_path']);
        if (! $verification['valid']) {
            throw new RuntimeException('The uploaded database backup failed checksum verification.');
        }
        $database['verified'] = true;
        $files = $withFiles ? $this->snapshotFiles() : null;
        $deleted = $this->pruneExpired();

        return [
            'ok' => true,
            'database' => $database,
            'files' => $files,
            'expired_files_deleted' => $deleted,
            'duration_seconds' => round(microtime(true) - $started, 2),
        ];
    }

    public function backupDatabase(): array
    {
        $connection = DB::connection();
        $tables = $this->orderedTables($connection);
        $stamp = now()->utc()->format('Y-m-d_H-i-s');
        $prefix = $this->prefix().'/database/'.now()->utc()->format('Y/m');
        $path = "{$prefix}/queenjackie_{$stamp}.jsonl.gz";
        $manifestPath = "{$path}.manifest.json";
        $temporary = tempnam(sys_get_temp_dir(), 'qj-backup-');

        if ($temporary === false) {
            throw new RuntimeException('Could not create a temporary backup file.');
        }

        $gzip = gzopen($temporary, 'wb9');
        if ($gzip === false) {
            @unlink($temporary);
            throw new RuntimeException('Could not open the compressed backup file.');
        }

        $counts = [];

        try {
            $connection->transaction(function () use ($connection, $tables, $gzip, &$counts): void {
                if ($connection->getDriverName() === 'pgsql') {
                    $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
                }

                $this->writeLine($gzip, [
                    'type' => 'header',
                    'format' => self::FORMAT_VERSION,
                    'created_at' => now()->utc()->toIso8601String(),
                    'driver' => $connection->getDriverName(),
                    'application' => config('app.name'),
                    'deployment' => env('VERCEL_GIT_COMMIT_SHA'),
                ]);

                foreach ($tables as $table) {
                    $count = 0;
                    $this->writeLine($gzip, ['type' => 'table', 'name' => $table]);

                    foreach ($connection->table($table)->cursor() as $row) {
                        $this->writeLine($gzip, ['type' => 'row', 'data' => (array) $row]);
                        $count++;
                    }

                    $this->writeLine($gzip, ['type' => 'end_table', 'name' => $table, 'rows' => $count]);
                    $counts[$table] = $count;
                }
            }, 3);

            gzclose($gzip);
            $gzip = null;

            $checksum = hash_file('sha256', $temporary);
            $size = filesize($temporary);
            $stream = fopen($temporary, 'rb');
            if ($stream === false || ! Storage::disk($this->disk())->put($path, $stream)) {
                throw new RuntimeException('Could not upload the database backup.');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            $manifest = [
                'format' => self::FORMAT_VERSION,
                'created_at' => now()->utc()->toIso8601String(),
                'backup_path' => $path,
                'sha256' => $checksum,
                'compressed_bytes' => $size,
                'tables' => $counts,
                'total_rows' => array_sum($counts),
            ];
            Storage::disk($this->disk())->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $manifest + ['manifest_path' => $manifestPath];
        } finally {
            if (is_resource($gzip)) {
                gzclose($gzip);
            }
            @unlink($temporary);
        }
    }

    public function snapshotFiles(): array
    {
        $sourceName = (string) config('operations.backup.source_disk');
        $targetName = $this->disk();
        $source = Storage::disk($sourceName);
        $target = Storage::disk($targetName);
        $backupPrefix = $this->prefix();
        $snapshot = $backupPrefix.'/storage/'.now()->utc()->format('Y/m/Y-m-d_H-i-s');
        $files = array_values(array_filter(
            $source->allFiles(),
            fn (string $path) => ! str_starts_with(ltrim($path, '/'), $backupPrefix.'/')
        ));
        $entries = [];

        foreach ($files as $path) {
            $destination = $snapshot.'/'.$path;
            // Supabase's S3 gateway does not support server-side CopyObject,
            // so stream the object even when source and destination share a disk.
            $stream = $source->readStream($path);
            if ($stream === false || ! $target->put($destination, $stream)) {
                throw new RuntimeException("Could not copy storage object [{$path}] to the backup disk.");
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
            $entries[] = ['path' => $path, 'bytes' => $source->size($path)];
        }

        $manifestPath = $snapshot.'/manifest.json';
        $target->put($manifestPath, json_encode([
            'created_at' => now()->utc()->toIso8601String(),
            'source_disk' => $sourceName,
            'objects' => $entries,
            'object_count' => count($entries),
            'total_bytes' => array_sum(array_column($entries, 'bytes')),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return ['path' => $snapshot, 'objects' => count($entries), 'manifest_path' => $manifestPath];
    }

    public function verify(string $path): array
    {
        $storage = Storage::disk($this->disk());
        $manifestPath = $path.'.manifest.json';
        if (! $storage->exists($path) || ! $storage->exists($manifestPath)) {
            throw new RuntimeException('Backup or its manifest does not exist.');
        }

        $manifest = json_decode($storage->get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $stream = $storage->readStream($path);
        if ($stream === false) {
            throw new RuntimeException('Could not read the backup.');
        }
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);
        $actual = hash_final($hash);

        return $manifest + ['valid' => hash_equals((string) ($manifest['sha256'] ?? ''), $actual), 'actual_sha256' => $actual];
    }

    public function latestDatabaseBackup(): ?string
    {
        $files = array_filter(
            Storage::disk($this->disk())->allFiles($this->prefix().'/database'),
            fn (string $path) => str_ends_with($path, '.jsonl.gz')
        );
        rsort($files);

        return $files[0] ?? null;
    }

    private function pruneExpired(): int
    {
        $days = max(1, (int) config('operations.backup.retention_days', 35));
        $cutoff = now()->subDays($days)->getTimestamp();
        $storage = Storage::disk($this->disk());
        $deleted = 0;

        foreach ($storage->allFiles($this->prefix()) as $path) {
            try {
                if ($storage->lastModified($path) < $cutoff && $storage->delete($path)) {
                    $deleted++;
                }
            } catch (Throwable) {
                // One unreadable object must not invalidate a completed backup.
            }
        }

        return $deleted;
    }

    /** @return list<string> */
    private function orderedTables(Connection $connection): array
    {
        $driver = $connection->getDriverName();
        if ($driver === 'pgsql') {
            $tables = array_map(fn ($row) => $row->table_name, $connection->select(
                "select table_name from information_schema.tables where table_schema = 'public' and table_type = 'BASE TABLE'"
            ));
            $relations = $connection->select("select tc.table_name as child, ccu.table_name as parent
                from information_schema.table_constraints tc
                join information_schema.constraint_column_usage ccu on ccu.constraint_name = tc.constraint_name and ccu.constraint_schema = tc.constraint_schema
                where tc.constraint_type = 'FOREIGN KEY' and tc.table_schema = 'public'");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            $tables = array_map(fn ($row) => $row->table_name, $connection->select(
                'select table_name from information_schema.tables where table_schema = database() and table_type = ?', ['BASE TABLE']
            ));
            $relations = $connection->select('select table_name as child, referenced_table_name as parent from information_schema.key_column_usage where table_schema = database() and referenced_table_name is not null');
        } elseif ($driver === 'sqlite') {
            $tables = array_map(fn ($row) => $row->name, $connection->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"));
            $relations = [];
            foreach ($tables as $table) {
                foreach ($connection->select('pragma foreign_key_list('.$connection->getQueryGrammar()->wrapTable($table).')') as $fk) {
                    $relations[] = (object) ['child' => $table, 'parent' => $fk->table];
                }
            }
        } else {
            throw new RuntimeException("Unsupported database driver [{$driver}].");
        }

        sort($tables);
        $ordered = [];
        while (count($ordered) < count($tables)) {
            $progress = false;
            foreach ($tables as $table) {
                if (in_array($table, $ordered, true)) {
                    continue;
                }
                $parents = array_map(fn ($relation) => $relation->parent, array_filter($relations, fn ($relation) => $relation->child === $table && $relation->parent !== $table));
                if (count(array_diff($parents, $ordered)) === 0) {
                    $ordered[] = $table;
                    $progress = true;
                }
            }
            if (! $progress) {
                // Cyclic constraints are unusual here; deterministic fallback keeps the backup usable.
                $ordered = array_values(array_unique(array_merge($ordered, $tables)));
            }
        }

        return $ordered;
    }

    private function writeLine($gzip, array $record): void
    {
        $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        if (gzwrite($gzip, $json."\n") === false) {
            throw new RuntimeException('Could not write the compressed backup.');
        }
    }

    private function disk(): string
    {
        return (string) config('operations.backup.disk');
    }

    private function prefix(): string
    {
        return (string) config('operations.backup.prefix');
    }
}
