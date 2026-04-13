<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use SyncLogger;
    protected $fillable = [
        'inventory_instance_id',
        'name',
        'category',
        'location',
        'value',
        'quantity',
        'status',
        'acquired_date',
        'granting_agency',
    ];

    public function instance()
    {
        return $this->belongsTo(InventoryInstance::class, 'inventory_instance_id');
    }

    public function itemPictures()
    {
        return $this->hasMany(ItemPicturesFiles::class, 'inventory_id');
    }

    public function moaFiles()
    {
        return $this->hasMany(MoaFile::class, 'inventory_id');
    }
}
