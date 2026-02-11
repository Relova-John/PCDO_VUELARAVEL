<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class Programs extends Model
{
    use SyncLogger;
    protected $fillable = [
        'name',
        'description',
        'details',
        'term_months',
        'grace_period',
        'min_amount',
        'max_amount',
        'penalty',
        'archive'
    ];

    public $timestamps = false;

    public function checklists()
    {
        return $this->belongsToMany(Checklists::class, 'program_checklists', 'program_id', 'checklist_id')->withPivot('id');
    }

    public function coopProgram()
    {
        return $this->hasMany(CoopProgram::class, 'program_id');
    }
}
