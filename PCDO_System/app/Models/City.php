<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(array $param)
 */
class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'region_code', 'province_code', 'is_city', 'city_class'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code', 'region_code', 'province_code'],
            'query_like' => ['name'],
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class, 'city_code', 'code');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CoopDetail::class, 'city_code', 'code');
    }
}
