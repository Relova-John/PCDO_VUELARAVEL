<?php

namespace App\Http\Controllers\Guest;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\Cooperative;
use App\Models\Inventory;
use App\Models\InventoryInstance;
use App\Models\ReportingDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class FormController extends Controller
{
    public function index()
    {
        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();

        return inertia('guest/Form', [
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'breadcrumbs' => [
                ['title' => 'Inventory', 'href' => route('form')],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_code' => 'required|exists:regions,code',
            'province_code' => 'required',
            'city_code' => 'required',
            'barangay_code' => 'required|exists:barangays,code',
            'email' => 'required|email|max:255',
            'number' => 'required|string|max:20',

            'inventoryItem' => 'nullable|array',
            'inventoryItem.*.category' => 'required|string|max:255',
            'inventoryItem.*.name' => 'required|string|max:255',
            'inventoryItem.*.granting_agency' => 'required|string|max:255',
            'inventoryItem.*.location' => 'required|string|max:255',
            'inventoryItem.*.value' => 'required|numeric',
            'inventoryItem.*.quantity' => 'required|integer',
            'inventoryItem.*.status' => 'nullable|integer',
            'inventoryItem.*.acquired_date' => 'required|date',
        ]);

        $reportingDate = ReportingDate::orderByDesc('reporting_year')
            ->orderByDesc('reporting_month')
            ->first();

        if (!$reportingDate) {
            $reportingDate = ReportingDate::create([
                'reporting_year' => now()->year,
                'reporting_month' => now()->month,
            ]);
        }

        DB::transaction(function () use ($validated, $reportingDate) {
            $coop = Cooperative::updateOrCreate(
                [
                    'name' => $validated['name'],
                ],
                [
                    'region_code' => $validated['region_code'],
                    'province_code' => $validated['province_code'],
                    'city_code' => $validated['city_code'],
                    'barangay_code' => $validated['barangay_code'],
                    'email' => $validated['email'] ?? null,
                    'number' => $validated['number'] ?? null,
                ]
            );

            $inventoryInstance = InventoryInstance::firstOrCreate(
                [
                    'coop_id' => $coop->id,
                    'reporting_date_id' => $reportingDate->id
                ]
            );

            $inventoryInstance->inventories()->delete();

            if (!empty($validated['inventoryItem'])) {
                foreach ($validated['inventoryItem'] as $item) {
                    Inventory::create([
                        'inventory_instance_id' => $inventoryInstance->id,
                        'category' => $item['category'] ?? null,
                        'name' => $item['name'] ?? null,
                        'granting_agency' => $item['granting_agency'] ?? null,
                        'location' => $item['location'] ?? null,
                        'value' => $item['value'] ?? null,
                        'quantity' => $item['quantity'] ?? null,
                        'status' => $item['status'] ?? null,
                        'acquired_date' => $item['acquired_date'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('form')->with('success', 'Inventory saved successfully');
    }
}
