<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class Delinquent extends Model
{
    use SyncLogger;

    protected $fillable = [
        'coop_program_id',
        'amortization_schedule_id',
        'due_date',
        'date_paid',
        'status',
    ];

    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class);
    }

    public function ammortization()
    {
        return $this->belongsTo(AmortizationSchedules::class, 'ammortization_schedule_id');
    }
}
