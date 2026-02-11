<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cooperative extends Model
{
    /** @use HasFactory<\Database\Factories\CooperativeFactory> */
    use HasFactory, SyncLogger;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'holder',
        'type', // 'primary', 'secondary', 'tertiary'
    ];

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function parent()
    {
        return $this->belongsTo(Cooperative::class, 'holder');
    }

    public function child()
    {
        return $this->hasMany(Cooperative::class, 'holder');
    }

    public function details()
    {
        return $this->hasOne(CoopDetail::class, 'coop_id', 'id');
    }

    public function members()
    {
        return $this->hasMany(CoopMember::class, 'coop_id', 'id');
    }

    public function programs()
    {
        return $this->hasMany(CoopProgram::class, 'coop_id', 'id');
    }

    public function oldPrograms()
    {
        return $this->hasMany(AmortizationOld::class, 'coop_program_id');
    }
    public function coopDetail()
    {
        return $this->hasOne(CoopDetail::class, 'coop_id', 'id');
    }

    public function progressReports()
    {
        return $this->hasManyThrough(
            CoopProgramProgress::class,
            CoopProgram::class,
            'coop_id',        // Foreign key on coop_programs
            'coop_program_id', // Foreign key on coop_program_progress
            'id',             // Local key on cooperatives
            'id'              // Local key on coop_programs
        );
    }

    public function isValidHierarchy()
    {
        if ($this->type === 'primary') {
            return $this->holder === null;
        }

        if ($this->type === 'secondary') {
            if (! $this->holder) {
                return false;
            }
            $parent = $this->parent ?? Cooperative::find($this->holder);

            return $parent && $parent->type === 'primary';
        }

        if ($this->type === 'tertiary') {
            if (! $this->holder) {
                return false;
            }
            $parent = $this->parent ?? Cooperative::find($this->holder);

            return $parent && $parent->type === 'secondary';
        }

        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cooperative) {
            if (! $cooperative->isValidHierarchy()) {
                throw new \Exception('Invalid cooperative hierarchy.');
            }
        });
    }
}
