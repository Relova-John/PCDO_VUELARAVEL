<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoopDetail extends Model
{
    protected $fillable = [
        'coop_id',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
        'asset_size',
        'coop_type',
        'status/category',
        'bond_of_membership',
        'area_of_operation',
        'citizenship',
        'members_count',
        'total_asset',
        'net_surplus',
    ];

    protected $table = 'coop_details';

    protected $primaryKey = 'coop_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }
}
