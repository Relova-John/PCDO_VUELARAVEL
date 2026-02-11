<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishedCoopProgramChecklist extends Model
{
    use HasFactory, SyncLogger;

    protected $table = 'finished_coop_program_checklist';

    protected $fillable = [
        'coop_program_id',
        'task_name',
        'status',
    ];

    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class, 'coop_program_id');
    }

    public function checklist()
    {
        return $this->belongsTo(CoopProgramChecklist::class, 'checklist_id');
    }
}
