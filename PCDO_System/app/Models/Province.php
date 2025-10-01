<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(array $param)
 */
class Province extends Model
{
    use HasFactory;

    protected $table = 'provinces';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'region_code'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code', 'region_code'],
            'query_like' => ['name'],
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'province_code', 'code');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CoopDetail::class, 'province_code', 'code');
    }
}
