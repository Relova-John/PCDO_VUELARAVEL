<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['admin', 'officer', 'user'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }
    }
}
