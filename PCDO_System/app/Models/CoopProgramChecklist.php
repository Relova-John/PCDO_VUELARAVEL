<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoopProgramChecklist extends Model
{
    use HasFactory, SyncLogger;

    protected $fillable = [
        'coop_program_id',
        'program_checklist_id',
        'checklist_id',
        'file_name',
        'mime_type',
        'file_content',
    ];

    // hide binary data from arrays / JSON
    protected $hidden = ['file_content'];

    public function coopProgram()
    {
        return $this->belongsTo(CoopProgram::class);
    }

    public function checklist()
    {
        return $this->belongsTo(Checklists::class);
    }

    /**
     * Safe accessor: only base64-encode when value exists.
     * Does NOT break when file_content is null.
     */
    public function getFileContentBase64Attribute()
    {
        return $this->attributes['file_content'] !== null
            ? base64_encode($this->attributes['file_content'])
            : null;
    }
}
