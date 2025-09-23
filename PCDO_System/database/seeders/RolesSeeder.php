<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['superadmin', 'admin', 'officer', 'user'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole('superadmin');
        }
    }
}
