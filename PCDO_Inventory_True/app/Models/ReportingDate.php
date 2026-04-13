<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class ReportingDate extends Model
{
    use SyncLogger;
    protected $fillable = [
        'reporting_year',
        'reporting_month',
    ];

    public $timestamps = false;
}
