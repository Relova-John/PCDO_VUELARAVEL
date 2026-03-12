<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessControl extends Model
{
    protected $fillable = [
        'created_by',
        'type',
        'token',
        'code',
        'is_active',
        'one_time',
        'max_uses',
        'used_count',
        'expires_at',
        'last_used_at',
        'closed_at',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'one_time' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }

    public function isClosed(): bool
    {
        return ! $this->is_active || $this->closed_at !== null;
    }

    public function hasRemainingUses(): bool
    {
        if ($this->one_time) {
            return $this->used_count < 1;
        }

        if ($this->max_uses === null) {
            return true;
        }

        return $this->used_count < $this->max_uses;
    }

    public function isUsable(): bool
    {
        return ! $this->isClosed()
            && ! $this->isExpired()
            && $this->hasRemainingUses();
    }
    
}