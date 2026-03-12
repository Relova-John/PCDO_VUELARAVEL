<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'access_control_id',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    public function isAdminLevel(): bool
    {
        return in_array($this->role, ['superadmin', 'admin'], true);
    }

    public function accessControl()
    {
        return $this->belongsTo(AccessControl::class);
    }

    public function activateByAccessControl($user, AccessControl $accessControl): void
    {
        if (! $accessControl->is_active || $accessControl->closed_at) {
            abort(422, 'This access code is no longer active.');
        }

        if ($accessControl->expires_at && now()->gt($accessControl->expires_at)) {
            abort(422, 'This access code has expired.');
        }

        if (! is_null($accessControl->max_uses) && $accessControl->used_count >= $accessControl->max_uses) {
            abort(422, 'This access code has reached its usage limit.');
        }

        DB::transaction(function () use ($user, $accessControl) {
            $user->update([
                'access_control_id' => $accessControl->id,
                'region_code' => $accessControl->region_code,
                'province_code' => $accessControl->province_code,
                'city_code' => $accessControl->city_code,
                'barangay_code' => $accessControl->barangay_code,
            ]);

            $accessControl->increment('used_count');

            $accessControl->update([
                'last_used_at' => now(),
                'is_active' => $accessControl->one_time ? false : $accessControl->is_active,
                'closed_at' => $accessControl->one_time ? now() : $accessControl->closed_at,
            ]);
        });
    }
}
