<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    protected $fillable = [
        'name',
        'province_id',
    ];
    public $timestamps = false;

    public function barangay()
    {
        return $this->hasMany(Barangay::class, 'barangay_id');
    }

    public function coopDetails()
    {
        return $this->hasMany(CoopDetail::class, 'municipality_id');
    }
}
