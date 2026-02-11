<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class AmortizationOld extends Model
{
    use SyncLogger;

    protected $fillable = [
        'coop_program_id',
        'file_content',

    ];

    protected $table = 'old';

    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class, 'coop_program_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}
