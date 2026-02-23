<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Http\Request;

class InventoryFormController extends Controller
{
    public function index()
    {
        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();

        return inertia('inventory/index', [
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'breadcrumbs' => [
                ['title' => 'Inventory', 'href' => route('inventory.index')],
            ],
        ]);
    }
}
