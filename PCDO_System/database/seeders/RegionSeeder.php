<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            'MIMAROPA' => [
                'Palawan' => [
                    'Aborlan' => [],
                    'Agutaya' => [],
                    'Araceli' => [],
                    'Balabac' => [],
                    'Bataraza' => [],
                    'Brooke\'s Point' => [],
                    'Busuanga' => [],
                    'Cagayancillo' => [],
                    'Coron' => [],
                    'Culion' => [],
                    'Cuyo' => [],
                    'Dumaran' => [],
                    'El Nido' => [],
                    'Kalayaan' => [],
                    'Linapacan' => [],
                    'Magsaysay' => [],
                    'Narra' => [],
                    'Puerto Princesa' => [
                        'San Jose',
                        'San Pedro',
                    ],
                    'Quezon' => [],
                    'Roxas' => [],
                    'San Vicente' => [],
                    'Sofronio Española' => [],
                    'Taytay' => [],
                ],
                'AnotherProvince' => [
                    'Municipality1' => [],
                    'Municipality2' => [],
                    'Municipality3' => [],
                ],
                'AnotherProvince2' => [] // must be array
            ],
            'AnotherRegion' => [
                'AnotherProvince3' => [
                    'Municipality4' => [
                        'Barangay1',
                        'Barangay2'
                    ],
                    'Municipality5' => [] // wrap as array
                ]
            ]
        ];
        foreach ($regions as $r => $provinces) {
            $region = Region::firstOrCreate(['name' => $r]);


            foreach ($provinces as $p => $municipalities) {
                $province = Province::firstOrCreate([
                    'name' => $p,
                    'region_id' => $region->id,
                ]);


                foreach ($municipalities as $m => $barangays) {
                    $municipalities = Municipality::firstOrCreate([
                        'name' => $m,
                        'province_id' => $province->id,
                    ]);


                    foreach ($barangays as $b) {
                        Barangay::firstOrCreate([
                            'name' => $b,
                            'municipality_id' => $municipalities->id,
                        ]);
                    }
                }
            }
        }
    }
}