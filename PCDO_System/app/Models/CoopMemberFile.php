<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoopMemberFile extends Model
{
    protected $fillable = [
        'coop_member_id',
        'file_path',
        'file_name',
        'file_type',
    ];

    public function member()
    {
        return $this->belongsTo(CoopMember::class, 'coop_member_id');
    }
}
