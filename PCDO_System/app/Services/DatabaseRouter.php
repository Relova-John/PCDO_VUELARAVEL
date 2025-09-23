<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseRouter
{
    protected static $connection = null;

    /**
     * Get active connection.
     * Cloud is master; local is read-only fallback.
     */
    public static function getConnection(): string
    {
        if (self::$connection) return self::$connection;

        try {
            DB::connection('cloud')->getPdo();
            return self::$connection = 'cloud';
        } catch (\Exception $e) {
            return self::$connection = 'local';
        }
    }

    /**
     * Safe sync from cloud to local only.
     * Avoids overwriting cloud data from local.
     */
    public static function syncToLocal(string $table): bool
    {
        $updated = false;
        $cloudData = DB::connection('cloud')->table($table)->get();

        DB::connection('local')->transaction(function () use ($cloudData, $table, &$updated) {
            foreach ($cloudData as $row) {
                $rowArray = (array)$row;
                $existing = DB::connection('local')->table($table)->where('id', $row->id)->first();

                if ($existing) {
                    if (isset($rowArray['updated_at'], $existing->updated_at) &&
                        Carbon::parse($rowArray['updated_at'])->gt(Carbon::parse($existing->updated_at))) {
                        DB::connection('local')->table($table)->where('id', $row->id)->update($rowArray);
                        $updated = true;
                    }
                } else {
                    DB::connection('local')->table($table)->insert($rowArray);
                    $updated = true;
                }
            }
        });

        return $updated;
    }

    /**
     * Safe sync from local to cloud only.
     * Avoids overwriting cloud data from local.
     */
    public static function syncToCloud(string $table): bool
    {
        $updated = false;
        $localData = DB::connection('local')->table($table)->get();

        DB::connection('cloud')->transaction(function () use ($localData, $table, &$updated) {
            foreach ($localData as $row) {
                $rowArray = (array)$row;
                $existing = DB::connection('cloud')->table($table)->where('id', $row->id)->first();

                if ($existing) {
                    if (isset($rowArray['updated_at'], $existing->updated_at) &&
                        Carbon::parse($rowArray['updated_at'])->gt(Carbon::parse($existing->updated_at))) {
                        DB::connection('cloud')->table($table)->where('id', $row->id)->update($rowArray);
                        $updated = true;
                    }
                } else {
                    DB::connection('cloud')->table($table)->insert($rowArray);
                    $updated = true;
                }
            }
        });

        return $updated;
    }

    /**
     * Read query helper (dynamic fallback)
     */
    public static function query(string $table)
    {
        $connection = self::getConnection();
        return DB::connection($connection)->table($table);
    }
}