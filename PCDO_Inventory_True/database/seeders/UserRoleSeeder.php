<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {

        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'officerI']);
        Role::create(['name' => 'officerII']);
        Role::create(['name' => 'guest']);

        DB::table('users')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Guest',
                'email' => 'isFake',
                'password' => Hash::make('password'),
                'is_active' => false,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        DB::table('user_role')->updateOrInsert(
            ['user_id' => 1, 'role_id' => 5],
            []
        );

        $user = User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_by' => null,
            ]
        );

        $user->hasRole('superadmin') || $user->roles()->attach(1);
    }
}
