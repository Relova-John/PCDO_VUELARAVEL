<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_code' => 'required|exists:regions,code',
            'province_code' => 'required|exists:provinces,code',
            'city_code' => 'required|exists:cities,code',
            'barangay_code' => 'required|exists:barangays,code',
            'email' => 'nullable|email|max:255',
            'number' => 'nullable|string|max:20',

            'inventoryItem' => 'nullable|array',
            'inventoryItem.*.category' => 'nullable|string|max:255',
            'inventoryItem.*.name' => 'nullable|string|max:255',
            'inventoryItem.*.guarantor_agency' => 'nullable|string|max:255',
            'inventoryItem.*.location' => 'nullable|string|max:255',
            'inventoryItem.*.value' => 'nullable|numeric',
            'inventoryItem.*.quantity' => 'nullable|integer',
            'inventoryItem.*.status' => 'nullable|string|max:100',
            'inventoryItem.*.acquired_date' => 'nullable|date',
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
                        'guarantor_agency' => $item['guarantor_agency'] ?? null,
                        'location' => $item['location'] ?? null,
                        'value' => $item['value'] ?? null,
                        'quantity' => $item['quantity'] ?? null,
                        'status' => $item['status'] ?? null,
                        'acquired_date' => $item['acquired_date'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Inventory saved successfully');
    }
}
