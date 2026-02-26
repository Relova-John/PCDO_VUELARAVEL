<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SyncDatabase extends Command
{
    protected $signature = 'sync:database';

    protected $description = 'Two-way database sync between local SQLite and cloud MySQL';

    protected string $logFile;

    public function handle()
    {
        $this->logFile = storage_path('logs/sync_errors.log');
        $this->logLine('=== Sync started at '.now().' ===');

        $this->info('🔄 Starting database sync...');

        try {
            DB::connection('mysql_cloud')->select('SELECT 1');
        } catch (Throwable $e) {
            $this->logError('Cannot reach cloud database: '.$e->getMessage());
            $this->error('❌ Cannot reach cloud database.');

            return 1;
        }

        $local = DB::connection('sqlite_local');
        $cloud = DB::connection('mysql_cloud');

        $lastSyncAt = Cache::get('last_sync_time');
        $now = Carbon::now();
        $this->line('🕓 Last sync: '.($lastSyncAt ?? 'Never'));

        $orderedTables = $this->getDependencyOrderedTables($local);

        // 1️⃣ Pull logs from both sources
        $cloudLogs = $this->getSyncLogs($cloud, $lastSyncAt, 'cloud');
        $localLogs = $this->getSyncLogs($local, $lastSyncAt, 'local');

        // 2️⃣ Apply both ways
        $this->applyLogs($local, $cloudLogs, 'cloud', $orderedTables);
        $this->applyLogs($cloud, $localLogs, 'local', $orderedTables);

        Cache::put('last_sync_time', $now, 86400);
        $this->info('✅ Sync completed successfully at '.$now);
        $this->logLine("=== Sync completed successfully at {$now} ===\n");

        return 0;
    }

    /**
     * Retrieve logs newer than last sync time
     */
    protected function getSyncLogs($db, $since, $source)
    {
        $query = $db->table('sync_logs')->orderBy('executed_at');
        if ($since) {
            $query->where('executed_at', '>', $since);
        }

        $logs = $query->get();
        $this->line("📦 Retrieved {$logs->count()} logs from {$source}");

        return $logs;
    }

    /**
     * Apply logs with retry and ordered tables
     */
    protected function applyLogs($targetDB, $logs, $source, $tableOrder)
    {
        if ($logs->isEmpty()) {
            $this->line("⚙️ No logs to sync from {$source}");

            return;
        }

        $pending = collect($logs);
        $maxRetries = 3;

        for ($attempt = 1; $attempt <= $maxRetries && $pending->isNotEmpty(); $attempt++) {
            $this->line("🔁 Attempt #{$attempt} ({$pending->count()} pending)");
            $failed = collect();

            foreach ($tableOrder as $table) {
                $tableLogs = $pending->where('table_name', $table);
                foreach ($tableLogs as $log) {
                    try {
                        $this->applyChange($targetDB, $log, $source);
                    } catch (Throwable $e) {
                        $failed->push($log);
                        $msg = "Failed {$log->operation} on {$log->table_name} (id {$log->record_id}): {$e->getMessage()}";
                        $this->warn("❌ {$msg}");
                        $this->logError($msg, $e);
                    }
                }
            }

            $pending = $failed;
        }

        if ($pending->isNotEmpty()) {
            $this->warn("⚠️ Some logs could not be applied after {$maxRetries} attempts:");
            foreach ($pending as $log) {
                $this->warn("   - {$log->table_name} (id {$log->record_id})");
                $this->logError("Unresolved after {$maxRetries} attempts: {$log->table_name} (id {$log->record_id})");
            }
        }
    }

    /**
     * Apply a single change
     */
    protected function applyChange($db, $log, $source)
    {
        $table = $log->table_name;
        $changes = is_string($log->changes) ? json_decode($log->changes, true) : $log->changes;

        if (! is_array($changes)) {
            throw new \Exception("Invalid changes data format for {$table}");
        }

        foreach ($changes as $key => $val) {
            if (is_array($val) && array_key_exists('after', $val)) {
                $changes[$key] = $val['after'];
            }
        }

        foreach ($changes as $key => $val) {
            if (preg_match('/_id$/', $key) && is_numeric($val) === false) {
                $changes[$key] = (string) $val;
            }
        }

        $id = (string) $log->record_id;

        if ($table === 'users' && isset($changes['password'])) {
            if (! preg_match('/^\$2y\$/', $changes['password'])) {
                $changes['password'] = bcrypt($changes['password']);
            }
        }

        if ($log->operation === 'create') {
            $exists = $db->table($table)->where('id', $id)->exists();

            if ($exists) {
                if ($source === 'cloud') {
                    $db->table($table)->where('id', $id)->update($changes);
                    $this->line("♻️ Replaced existing {$table} id {$id} from cloud data");
                } else {
                    $newId = $this->getNextAvailableId($db, $table);
                    $changes['id'] = $newId;
                    $db->table($table)->insert($changes);
                    $this->warn("⚠️ Conflict on {$table} id {$id}, inserted as id {$newId}");
                }
            } else {
                $db->table($table)->insert($changes);
            }
        } elseif ($log->operation === 'update') {
            $db->table($table)->where('id', $id)->update($changes);
        } elseif ($log->operation === 'delete') {
            $db->table($table)->where('id', $id)->delete();
        }
    }

    /**
     * Determine next available numeric ID (or fallback to UUID-like)
     */
    protected function getNextAvailableId($db, $table)
    {
        try {
            $maxId = $db->table($table)->max('id');
            if (is_numeric($maxId)) {
                return (int) $maxId + 1;
            }
        } catch (Throwable $e) {
            // fallback for non-numeric ids
        }

        return uniqid('', true);
    }

    /**
     * Determine dependency-safe table order using FK graph
     */
    protected function getDependencyOrderedTables($connection): array
    {
        $tables = collect(
            $connection->select('SELECT name FROM sqlite_master WHERE type="table"')
        )
            ->pluck('name')
            ->reject(fn ($t) => in_array($t, ['sqlite_sequence', 'sync_logs']))
            ->values()
            ->all();

        $graph = [];
        foreach ($tables as $table) {
            $deps = [];
            $foreignKeys = $connection->select("PRAGMA foreign_key_list('$table')");
            foreach ($foreignKeys as $fk) {
                if (isset($fk->table) && in_array($fk->table, $tables)) {
                    $deps[] = $fk->table;
                }
            }
            $graph[$table] = $deps;
        }

        $sorted = [];
        $visited = [];

        $visit = function ($table) use (&$visit, &$sorted, &$visited, $graph) {
            if (isset($visited[$table])) {
                return;
            }
            $visited[$table] = true;
            foreach ($graph[$table] ?? [] as $dep) {
                $visit($dep);
            }
            $sorted[] = $table;
        };

        foreach ($tables as $t) {
            $visit($t);
        }

        $sorted = array_unique($sorted);
        if (empty($sorted)) {
            $sorted = $tables;
        }

        $this->line('📚 Table sync order: '.implode(', ', $sorted));

        return $sorted;
    }

    /**
     * === Logging Helpers ===
     */
    protected function logLine(string $text): void
    {
        File::append($this->logFile, '['.now().'] '.$text.PHP_EOL);
    }

    protected function logError(string $message, ?Throwable $e = null): void
    {
        $entry = '['.now().'] ERROR: '.$message;
        if ($e) {
            $entry .= PHP_EOL.$e->getTraceAsString();
        }
        File::append($this->logFile, $entry.PHP_EOL);
    }
}
