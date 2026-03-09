<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingDate extends Model
{
    protected $fillable = [
        'reporting_year',
        'reporting_month',
    ];

    public $timestamps = false;
}
