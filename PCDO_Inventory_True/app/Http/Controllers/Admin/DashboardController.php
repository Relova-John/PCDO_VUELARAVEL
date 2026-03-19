<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cooperative;
use App\Models\ReportingDate;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Inventory;
use App\Models\InventoryInstance;
use App\Models\Province;
use App\Models\Region;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $reportingDates = ReportingDate::orderByDesc('reporting_year')
            ->orderByDesc('reporting_month')
            ->get();

        if ($reportingDates->isEmpty()) {
            $reportingDate = ReportingDate::create([
                'reporting_year' => now()->year,
                'reporting_month' => now()->month,
            ]);

            $reportingDates = collect([$reportingDate]);
        }

        $reportingDateId = $request->reporting_date_id ?? $reportingDates->first()->id;

        $reportingDate = ReportingDate::find($reportingDateId);

        $cooperatives = Cooperative::select('id', 'name')->get();

        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();

        $inventoryCounts = DB::table('inventory_instances')
            ->join('inventories', 'inventory_instances.id', '=', 'inventories.inventory_instance_id')
            ->where('inventory_instances.reporting_date_id', $reportingDateId)
            ->select(
                'inventory_instances.coop_id',
                DB::raw('COUNT(inventories.id) as count')
            )
            ->groupBy('inventory_instances.coop_id')
            ->pluck('count', 'coop_id');

        return inertia('admin/Dashboard', [
            'cooperatives' => $cooperatives,
            'inventoryCounts' => $inventoryCounts,
            'reportingDate' => $reportingDate,
            'reportingDates' => $reportingDates,
            'selectedReportingDate' => $reportingDateId,
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('admin.dashboard')]
            ]
        ]);
    }

    public function showDetails(Request $request, $id)
    {
        $reportingDateId = $request->reporting_date_id
            ?? ReportingDate::orderByDesc('reporting_year')
            ->orderByDesc('reporting_month')
            ->value('id');

        $reportingDate = ReportingDate::find($reportingDateId);

        $cooperative = Cooperative::with([
            'region',
            'province',
            'city',
            'barangay',
            'instances' => function ($q) use ($reportingDateId) {
                $q->where('reporting_date_id', $reportingDateId)
                    ->with('inventories');
            }
        ])->findOrFail($id);

        return inertia('admin/ShowDetails', [
            'cooperative' => $cooperative,
            'reportingDate' => $reportingDate,
            'reportingDateId' => $reportingDateId,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('admin.dashboard')],
                ['title' => 'Cooperative Details', 'href' => route('admin.dashboard.showdetails', $id)]
            ]
        ]);
    }
    public function updateReportingDates(Request $request)
    {
        $validated = $request->validate([
            'reporting_month' => 'required|integer|min:1|max:12',
            'reporting_year' => 'required|integer'
        ]);

        $reportingDate = ReportingDate::firstOrCreate(
            [
                'reporting_month' => $validated['reporting_month'],
                'reporting_year' => $validated['reporting_year']
            ]
        );

        return back()->with('success', 'Reporting date saved successfully');
    }

    public function edit($id)
    {
        $cooperative = Cooperative::with([
            'instances.inventories',
        ])->findOrFail($id);

        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();

        $inventories = [];

        if ($cooperative->instances->isNotEmpty()) {
            foreach ($cooperative->instances->first()->inventories as $inv) {
                $inventories[] = [
                    'category' => $inv->category,
                    'name' => $inv->name,
                    'granting_agency' => $inv->granting_agency,
                    'location' => $inv->location,
                    'value' => $inv->value,
                    'quantity' => $inv->quantity,
                    'status' => $inv->status,
                    'acquired_date' => $inv->acquired_date,
                ];
            }
        }
        $inventoryNames = DB::table('inventories')
            ->select('id', 'name', 'category')
            ->get();

        return inertia('admin/Edit', [
            'cooperative' => $cooperative,
            'inventoryItem' => $inventories,
            'regions' => $regions,
            'provinces' => $provinces,
            'inventoryNames' => $inventoryNames,
            'cities' => $cities,
            'barangays' => $barangays,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_code' => 'required',
            'province_code' => 'required',
            'city_code' => 'required',
            'barangay_code' => 'required',
            'email' => 'required|email',
            'number' => 'required',

            'inventoryItem' => 'nullable|array',
            'inventoryItem.*.category' => 'required|string',
            'inventoryItem.*.name' => 'required|string',
            'inventoryItem.*.granting_agency' => 'required|string',
            'inventoryItem.*.location' => 'required|string',
            'inventoryItem.*.value' => 'required|numeric',
            'inventoryItem.*.quantity' => 'required|integer',
            'inventoryItem.*.status' => 'nullable|integer',
            'inventoryItem.*.acquired_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $id) {
            $coop = Cooperative::findOrFail($id);

            $coop->update([
                'name' => $validated['name'],
                'region_code' => $validated['region_code'],
                'province_code' => $validated['province_code'],
                'city_code' => $validated['city_code'],
                'barangay_code' => $validated['barangay_code'],
                'email' => $validated['email'],
                'number' => $validated['number'],
            ]);

            // FIX: Find the correct reporting date using year/month instead of created_at
            $reportingDate = ReportingDate::orderByDesc('reporting_year')
                ->orderByDesc('reporting_month')
                ->first();

            $instance = InventoryInstance::firstOrCreate([
                'coop_id' => $coop->id,
                'reporting_date_id' => $reportingDate ? $reportingDate->id : null,
            ]);

            $instance->inventories()->delete();

            foreach ($validated['inventoryItem'] as $item) {
                Inventory::create([
                    'inventory_instance_id' => $instance->id,
                    'category' => $item['category'],
                    'name' => $item['name'],
                    'granting_agency' => $item['granting_agency'],
                    'location' => $item['location'],
                    'value' => $item['value'],
                    'quantity' => $item['quantity'],
                    'status' => $item['status'],
                    'acquired_date' => $item['acquired_date'],
                ]);
            }
        });

        return redirect()->route('admin.dashboard.show', $id);
    }
}
