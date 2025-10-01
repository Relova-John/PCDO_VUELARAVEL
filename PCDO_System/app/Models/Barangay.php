<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(array $param)
 */
class Barangay extends Model
{
    use HasFactory;

    protected $table = 'barangays';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'city_code'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code', 'city_code'],
            'query_like' => ['name'],
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CoopDetail::class, 'barangay_code', 'code');
    }
}
