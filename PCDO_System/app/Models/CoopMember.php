<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoopMember extends Model
{
    /** @use HasFactory<\Database\Factories\CoopMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'coop_id',
        'position',
        'first_name',
        'last_name',
        'middle_initial',
        'suffix',
        'is_representative',
        'date_of_birth',
        'active_year',
    ];

    public function cooperatives()
    {
        return $this->belongsToOne(Cooperative::class, 'coop_member_cooperative', 'coop_member_id', 'cooperative_id');
    }

    public function files()
    {
        return $this->hasMany(CoopMemberFile::class, 'coop_member_id');
    }
}
