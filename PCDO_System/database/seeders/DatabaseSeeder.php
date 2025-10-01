<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PSGCSeeder::class,
            CooperativeSeeder::class,
            ProgramSeeder::class,
            CoopDetailSeeder::class,
            ChecklistsSeeder::class,
            ProgramChecklistsSeeder::class,
            CoopProgramSeeder::class,
            RolesSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
