<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\AccessControl;
use App\Models\Cooperative;
use App\Models\ReportingDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\Inventory;
use App\Models\InventoryInstance;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $pendingCode = $request->session()->pull('pending_access_code');

        if ($pendingCode && ! $user->access_control_id) {
            $accessControl = AccessControl::query()
                ->where('code', $pendingCode)
                ->where('type', 'access')
                ->first();

            if ($accessControl && $accessControl->isUsable()) {
                DB::transaction(function () use ($user, $accessControl) {
                    $user->update([
                        'access_control_id' => $accessControl->id,
                        'region_code' => $accessControl->region_code,
                        'province_code' => $accessControl->province_code,
                        'city_code' => $accessControl->city_code,
                        'barangay_code' => $accessControl->barangay_code,
                    ]);

                    $accessControl->increment('used_count');

                    $accessControl->update([
                        'last_used_at' => now(),
                        'is_active' => $accessControl->one_time ? false : $accessControl->is_active,
                        'closed_at' => $accessControl->one_time ? now() : $accessControl->closed_at,
                    ]);
                });

                return redirect()
                    ->route('officer.dashboard')
                    ->with('success', 'Access code activated successfully.');
            }
        }

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

        $hasAccess = !is_null($user->access_control_id);

        if (! $hasAccess) {
            return inertia('officer/Dashboard', [
                'locked' => true,
                'reportingDate' => $reportingDate,
                'reportingDates' => $reportingDates,
                'selectedReportingDate' => (int) $reportingDateId,
                'cooperatives' => [],
                'inventoryCounts' => [],
                'locationScope' => null,
                'locationName' => null,
            ]);
        }

        $cooperativesQuery = Cooperative::query()->select('id', 'name', 'region_code', 'province_code', 'city_code', 'barangay_code');

        $this->applyLocationScope($cooperativesQuery, $user);

        $cooperatives = $cooperativesQuery
            ->orderBy('name')
            ->get();

        $coopIds = $cooperatives->pluck('id');

        $inventoryCounts = DB::table('inventory_instances')
            ->join('inventories', 'inventory_instances.id', '=', 'inventories.inventory_instance_id')
            ->where('inventory_instances.reporting_date_id', $reportingDateId)
            ->whereIn('inventory_instances.coop_id', $coopIds)
            ->select(
                'inventory_instances.coop_id',
                DB::raw('COUNT(inventories.id) as count')
            )
            ->groupBy('inventory_instances.coop_id')
            ->pluck('count', 'coop_id');

        return inertia('officer/Dashboard', [
            'locked' => false,
            'cooperatives' => $cooperatives,
            'inventoryCounts' => $inventoryCounts,
            'reportingDate' => $reportingDate,
            'reportingDates' => $reportingDates,
            'selectedReportingDate' => (int) $reportingDateId,
            'locationScope' => $this->buildLocationScope($user),
            'locationName' => $this->buildLocationName($user),
        ]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        $accessControl = AccessControl::query()
            ->where('code', $request->code)
            ->where('type', 'access')
            ->first();

        if (! $accessControl) {
            return back()->withErrors([
                'code' => 'Invalid access code.',
            ]);
        }

        $user->activateByAccessControl($user, $accessControl);

        return redirect()
            ->route('officer.dashboard')
            ->with('success', 'Access code activated successfully.');
    }
    public function showDetails(Request $request, $id)
    {
        $user = $request->user();

        if (! $user->access_control_id) {
            return redirect()
                ->route('officer.dashboard')
                ->withErrors([
                    'code' => 'You must activate an access code first.',
                ]);
        }

        $reportingDateId = $request->reporting_date_id
            ?? ReportingDate::orderByDesc('reporting_year')
            ->orderByDesc('reporting_month')
            ->value('id');

        $reportingDate = ReportingDate::find($reportingDateId);

        $cooperativeQuery = Cooperative::query()
            ->with([
                'region',
                'province',
                'city',
                'barangay',
                'instances' => function ($q) use ($reportingDateId) {
                    $q->where('reporting_date_id', $reportingDateId)
                        ->with('inventories');
                }
            ]);

        $this->applyLocationScope($cooperativeQuery, $user);

        $cooperative = $cooperativeQuery
            ->where('id', $id)
            ->firstOrFail();

        return inertia('officer/ShowDetails', [
            'cooperative' => $cooperative,
            'reportingDate' => $reportingDate,
            'reportingDateId' => $reportingDateId,
        ]);
    }

    private function applyLocationScope($query, $user): void
    {
        if ($user->barangay_code) {
            $query->where('barangay_code', $user->barangay_code);
            return;
        }

        if ($user->city_code) {
            $query->where('city_code', $user->city_code);
            return;
        }

        if ($user->province_code) {
            $query->where('province_code', $user->province_code);
            return;
        }

        if ($user->region_code) {
            $query->where('region_code', $user->region_code);
        }
    }

    private function buildLocationScope($user): ?string
    {
        if ($user->barangay_code) return 'Barangay Scope';
        if ($user->city_code) return 'City / Municipality Scope';
        if ($user->province_code) return 'Province Scope';
        if ($user->region_code) return 'Region Scope';

        return null;
    }

    private function buildLocationName($user): ?string
    {
        if ($user->barangay_code) {
            return Barangay::where('code', $user->barangay_code)->value('name');
        }

        if ($user->city_code) {
            return City::where('code', $user->city_code)->value('name');
        }

        if ($user->province_code) {
            return Province::where('code', $user->province_code)->value('name');
        }

        if ($user->region_code) {
            return Region::where('code', $user->region_code)->value('name');
        }

        return null;
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
                    'guarantor_agency' => $inv->guarantor_agency,
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

        return inertia('officer/Edit', [
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
            'inventoryItem.*.guarantor_agency' => 'required|string',
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
                    'guarantor_agency' => $item['guarantor_agency'],
                    'location' => $item['location'],
                    'value' => $item['value'],
                    'quantity' => $item['quantity'],
                    'status' => $item['status'],
                    'acquired_date' => $item['acquired_date'],
                ]);
            }
        });

        return redirect()->route('dashboard.show', $id);
    }
}
