<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['admin', 'officer', 'superadmin', 'cooperative'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $superadmin = User::updateOrCreate(
            ['email' => 'johnmichael.relova118@gmail.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('PCDO2023'),
                'email_verified_at' => now(),
            ]
        );

        $superadmin->assignRole('superadmin');
    }
}
