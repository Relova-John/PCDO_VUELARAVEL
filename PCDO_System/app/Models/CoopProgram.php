<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoopProgram extends Model
{
    /** @use HasFactory<\Database\Factories\CoopProgramFactory> */
    use HasFactory, SyncLogger;

    protected $fillable = [
        'coop_id',
        'program_id',
        'name',
        'description',
        'project',
        'start_date',
        'end_date',
        'program_status',
        'number',
        'email',
        'loan_amount',
        'with_grace',
        'exported',
        'consenter',
    ];

    protected $casts = [
        'coop_id' => 'string',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'coop_id', 'id');
    }

    public function program()
    {
        return $this->belongsTo(Programs::class, 'program_id');
    }

    public function checklist()
    {
        return $this->hasMany(CoopProgramChecklist::class);
    }

    public function amortizationSchedules()
    {
        return $this->hasMany(AmortizationSchedules::class, 'coop_program_id');
    }

    public function olds()
    {
        return $this->hasMany(AmortizationOld::class, 'coop_program_id');
    }

    public function old()
    {
        return $this->hasOne(AmortizationOld::class, 'coop_program_id');
    }

    public function programProgress()
    {
        return $this->hasMany(CoopProgramProgress::class, 'coop_program_id');
    }

    public function coopDetail()
    {
        return $this->hasOne(CoopDetail::class, 'coop_id', 'coop_id');
    }

    public function coopDetails()
    {
        return $this->hasMany(CoopDetail::class, 'coop_id', 'coop_id');
    }

    public function coopMemberFiles()
    {
        return $this->hasMany(CoopMemberFile::class, 'coop_member_id');
    }

    public function finishedChecklist()
    {
        return $this->hasMany(FinishedCoopProgramChecklist::class, 'coop_program_id');
    }

    public function delinquents()
    {
        return $this->hasMany(Delinquent::class, 'coop_program_id');
    }

    public function resolvedItems()
    {
        return $this->hasMany(Resolved::class, 'coop_program_id');
    }

    public function generateChecklists()
    {
        $items = $this->program->checklists;
        foreach ($items as $item) {
            CoopProgramChecklist::firstOrCreate([
                'coop_program_id' => $this->id,
                'checklist_id' => $item->id,
            ]);
        }
    }
}
