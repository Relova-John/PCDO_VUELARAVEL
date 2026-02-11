<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoopProgramProgress extends Model
{
    use HasFactory, SyncLogger;

    protected $fillable = [
        'coop_program_id',
        'title',
        'description',
        'file_name',
        'mime_type',
        'file_content',
    ];

    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class);
    }
}
