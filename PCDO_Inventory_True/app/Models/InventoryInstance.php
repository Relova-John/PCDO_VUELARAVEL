<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class InventoryInstance extends Model
{
    use SyncLogger;
    protected $fillable = [
        'coop_id',
        'reporting_date_id',
    ];

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function reportingDate()
    {
        return $this->belongsTo(ReportingDate::class, 'reporting_date_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'inventory_instance_id');
    }
}
