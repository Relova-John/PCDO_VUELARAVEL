<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocationAccess extends Model
{
    protected $table = 'user_location_accesses_scope';

    protected $fillable = [
        'user_id',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
