<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use Illuminate\Database\Seeder;

class CooperativeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [];

        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'name' => "Cooperative $i",
                'email' => "coop$i@example.com",
                'number' => '091234567' . $i,

                // ⚠️ REQUIRED FIELDS — use valid PSGC codes from your DB
                'region_code' => '040000000',     // example: Region IV-A
                'province_code' => '043400000',   // example: Laguna
                'city_code' => '043405000',       // example: Calamba (adjust if needed)
                'barangay_code' => null,          // optional

                'created_by' => null,
                'updated_by' => null,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Cooperative::insert($data);
    }
}