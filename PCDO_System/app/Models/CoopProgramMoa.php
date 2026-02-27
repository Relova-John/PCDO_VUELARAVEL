<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class CoopProgramMoa extends Model
{
    use SyncLogger;
    protected $table = 'coop_program_moa'; 
    protected $fillable = [
        'coop_program_id',
        'file_path',
        'file_name',
        'file_type',
    ];

    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class, 'coop_program_id');
    }
}
