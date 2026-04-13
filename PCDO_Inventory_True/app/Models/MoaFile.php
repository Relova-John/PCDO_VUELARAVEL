<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class MoaFile extends Model
{
    use SyncLogger;
    protected $table = 'moa_files';

    protected $fillable = [
        'inventory_id',
        'file_name',
        'file_path',
        'file_type',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
