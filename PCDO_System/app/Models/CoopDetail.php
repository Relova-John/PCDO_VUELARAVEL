<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class CoopDetail extends Model
{
    use SyncLogger;

    protected $fillable = [
        'coop_id',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
        'asset_size',
        'coop_type',
        'status_category',
        'bond_of_membership',
        'area_of_operation',
        'citizenship',
        'members_count',
        'total_asset',
        'net_surplus',
        'number',
        'email',
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
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_code', 'code');
    }
}
