<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'inventory_instance_id',
        'name',
        'category',
        'location',
        'value',
        'quantity',
        'status',
        'acquired_date',
        'guarantor_agency',
    ];

    public function instance()
    {
        return $this->belongsTo(InventoryInstance::class, 'inventory_instance_id');
    }
}
