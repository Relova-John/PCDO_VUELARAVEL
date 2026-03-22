<?php

namespace App\Traits;

use App\Models\SyncLog;
use Illuminate\Support\Facades\Auth;

trait SyncLogger
{
    protected static function bootSyncLogger()
    {
        static::created(function ($model) {
            self::writeSyncLog('create', $model, null);
        });

        static::updated(function ($model) {
            $changes = [];
            foreach ($model->getChanges() as $field => $new) {
                $old = $model->getOriginal($field);
                if ($old != $new) {
                    $changes[$field] = ['before' => $old, 'after' => $new];
                }
            }
            self::writeSyncLog('update', $model, $changes);
        });

        static::deleted(function ($model) {
            self::writeSyncLog('delete', $model, null);
        });
    }

    protected static function writeSyncLog($operation, $model, $changes)
    {
        $user = Auth::user();
        $source = config('database.default');

        $safeChanges = self::sanitizeForJson($changes ?? $model->getAttributes());

        SyncLog::create([
            'table_name' => $model->getTable(),
            'operation' => $operation,
            'record_id' => $model->id,
            'user_id' => $user?->id ?? 0,
            'user_name' => $user?->name ?? 'system',
            'changes' => $safeChanges,
            'source' => $source,
            'executed_at' => now(),
        ]);
    }

    protected static function sanitizeForJson($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeForJson'], $data);
        }

        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        return $data;
    }
}
