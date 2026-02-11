<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class AmortizationSchedules extends Model
{
    use SyncLogger;

    protected $fillable = [
        'coop_program_id',
        'due_date',
        'installment',
        'status',
        'date_paid',
        'amount_paid',
        'penalty_amount',
        'balance',
        'notes',
        'receipt_image',
    ];

    protected $hidden = ['receipt_image'];

    protected $casts = [
        'due_date' => 'date',
        'date_paid' => 'date',
    ];

    public function markPaid()
    {
        $this->status = 'Paid'; // make it consistent
        $this->save();
    }

    /**
     * Relationship to CoopProgram
     */
    public function program()
    {
        return $this->belongsTo(\App\Models\Programs::class, 'program_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(\App\Models\Cooperative::class, 'coop_id');
    }

    public function coopProgram()
    {
        return $this->belongsTo(\App\Models\CoopProgram::class, 'coop_program_id');
    }

    public function pendingnotifications()
    {
        return $this->hasOne(PendingNotification::class, 'schedule_id', 'id');
    }

    protected static function booted()
    {
        static::updated(function ($schedule) {
            if ($schedule->wasChanged('status') && $schedule->status === 'Paid') {
                $schedule->checkIfLastSchedulePaid();
            }
        });
    }

    public function checkIfLastSchedulePaid()
    {
        $coopProgram = $this->coopProgram;
        if (! $coopProgram) {
            return;
        }

        $lastSchedule = $coopProgram->amortizationSchedules()
            ->orderByDesc('due_date')
            ->first();

        if ($lastSchedule && $lastSchedule->status === 'Paid' && $coopProgram->program_status !== 'Finished') {
            $coopProgram->program_status = 'Finished';
            $coopProgram->save();
        }
    }
}
