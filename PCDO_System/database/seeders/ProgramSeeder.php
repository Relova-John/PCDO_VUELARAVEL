<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Programs;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            ['name' => 'USAD', 'details' => 'Upgrading Support for Advancement and Development of Enterprises in Cooperative', 'term_months' => 24, 'grace_period' => 4, 'min_amount' => 201000, 'max_amount' => 300000],
            ['name' => 'SULONG', 'details' => 'Sustainable Livelihood Opportunities and Growth', 'term_months' => 24, 'grace_period' => 4, 'min_amount' => 301000, 'max_amount' => 500000],
            ['name' => 'LICAP', 'details' => 'Livelihood and Credit Assistance Program', 'term_months' => 16, 'grace_period' => 4, 'min_amount' => 100000, 'max_amount' => 200000],
            ['name' => 'COPSE', 'details' => 'Cooperative Program For Sustainable Enterprises', 'term_months' => 24, 'grace_period' => 4, 'min_amount' => 501000, 'max_amount' => 750000],
            ['name' => 'PCLRP', 'details' => 'Provincial Cooperative Livelihood Recovery Program', 'term_months' => 16, 'grace_period' => 4, 'min_amount' => 100000, 'max_amount' => 255555],
        ];

        foreach ($programs as $data) {
            Programs::create($data);
        }
    }
}
