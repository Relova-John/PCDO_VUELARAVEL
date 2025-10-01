<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\CoopDetail;
use App\Models\Cooperative;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CoopDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regionCode = Region::pluck('code')->toArray();
        $provinceCode = Province::pluck('code')->toArray();
        $cityCode = City::pluck('code')->toArray();
        $barangayCode = Barangay::pluck('code')->toArray();
        foreach (Cooperative::all() as $coop) {
            CoopDetail::create([
                'coop_id' => $coop->id,
                'region_code' => $regionCode[array_rand($regionCode)],
                'province_code' => $provinceCode[array_rand($provinceCode)],
                'city_code' => $cityCode[array_rand($cityCode)],
                'barangay_code' => $barangayCode[array_rand($barangayCode)],
                'asset_size' => ['Micro', 'Small', 'Medium', 'Large', 'Unclassified'][rand(0, 4)],
                'coop_type' => ['Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose', 'Advocacy', 'Agrarian Reform', 'Bank', 'Diary', 'Education', 'Electric', 'Financial', 'Fishermen', 'Health Services', 'Housing', 'Insurance', 'Water Service', 'Workers', 'Others'][rand(0, 19)],
                'status_category' => ['Reporting', 'Non-Reporting', 'New'][rand(0, 2)],
                'bond_of_membership' => ['Residential', 'Insitutional', 'Associational', 'Occupational', 'Unspecified'][rand(0, 4)],
                'area_of_operation' => ['Municipal', 'Provincial'][rand(0, 1)],
                'citizenship' => ['Filipino', 'Foreign', 'Others'][rand(0, 2)],
                'members_count' => rand(10, 500),
                'total_asset' => rand(100000, 5000000),
                'net_surplus' => rand(5000, 200000),
            ]);
        }
    }
}
