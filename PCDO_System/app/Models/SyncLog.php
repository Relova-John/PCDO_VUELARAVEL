<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_logs';

    protected $fillable = [
        'table_name',
        'operation',
        'record_id',
        'user_id',
        'user_name',
        'changes',
        'source',
        'executed_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'executed_at' => 'datetime',
    ];

    public $timestamps = true;
}
