<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory, SyncLogger;

    protected $fillable = [
        'schedule_id',
        'coop_id',
        'type',
        'subject',
        'body',
        'processed',
    ];

    public function schedule()
    {
        return $this->belongsTo(AmortizationSchedules::class, 'schedule_id');
    }

    // link directly to cooperative
    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class, 'coop_id', 'coop_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}
