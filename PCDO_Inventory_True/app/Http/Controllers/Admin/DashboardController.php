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
use App\Models\MoaFile;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemPicturesFiles;
use Illuminate\Support\Str;

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

        $cooperatives = Cooperative::select('id', 'name', 'region_code', 'province_code', 'city_code', 'barangay_code')->get();

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

    public function create()
    {
        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();
        $inventoryNames = DB::table('inventories')
            ->selectRaw('MIN(id) as id, MIN(name) as name, category')
            ->groupBy('category', DB::raw('LOWER(TRIM(name))'))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $grantingAgencyNames = DB::table('inventories') // self should be constant option, not from db
            ->selectRaw('MIN(id) as id, MIN(granting_agency) as name')
            ->whereNotNull('granting_agency')
            ->whereRaw('TRIM(granting_agency) != ""')
            ->whereRaw('LOWER(TRIM(granting_agency)) != "self"')
            ->groupBy(DB::raw('LOWER(TRIM(granting_agency))'))
            ->orderBy('name')
            ->get();

        $grantingAgencyNames->prepend((object) ['id' => null, 'name' => 'Self']);

        return inertia('admin/Form', [
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'inventoryNames' => $inventoryNames,
            'grantingAgencyNames' => $grantingAgencyNames,
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

            'inventoryItem.*.item_picture' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'inventoryItem.*.moa_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $reportingDate = ReportingDate::orderByDesc('reporting_year')
            ->orderByDesc('reporting_month')
            ->first();

        if (! $reportingDate) {
            $reportingDate = ReportingDate::create([
                'reporting_year' => now()->year,
                'reporting_month' => now()->month,
            ]);
        }

        DB::transaction(function () use ($validated, $request, $reportingDate) {
            $coop = Cooperative::updateOrCreate(
                ['name' => $validated['name']],
                [
                    'region_code' => $validated['region_code'],
                    'province_code' => $validated['province_code'],
                    'city_code' => $validated['city_code'],
                    'barangay_code' => $validated['barangay_code'],
                    'email' => $validated['email'] ?? null,
                    'number' => $validated['number'] ?? null,
                ]
            );

            $inventoryInstance = InventoryInstance::firstOrCreate([
                'coop_id' => $coop->id,
                'reporting_date_id' => $reportingDate->id,
            ]);

            if (! empty($validated['inventoryItem'])) {
                foreach ($validated['inventoryItem'] as $index => $item) {
                    $normalizedItemName = $this->normalizeItemName($item['name']);
                    $inventory = Inventory::updateOrCreate(
                        [
                            'inventory_instance_id' => $inventoryInstance->id,
                            'category' => $item['category'],
                            'name' => $normalizedItemName,
                        ],
                        [
                            'granting_agency' => $item['granting_agency'] ?? null,
                            'location' => $item['location'] ?? null,
                            'value' => $item['value'] ?? null,
                            'quantity' => $item['quantity'] ?? null,
                            'status' => $item['status'] ?? null,
                            'acquired_date' => $item['acquired_date'] ?? null,
                        ]
                    );

                    $date = now()->format('m-d-Y');
                    $coopName = Str::slug($coop->name);
                    $itemCategory = Str::slug($inventory->category);
                    $itemName = Str::slug($inventory->name);

                    if ($request->hasFile("inventoryItem.$index.item_picture")) {
                        $oldItemPicture = ItemPicturesFiles::where('inventory_id', $inventory->id)->first();

                        if ($oldItemPicture && $oldItemPicture->file_path) {
                            Storage::disk('public')->delete($oldItemPicture->file_path);
                            $oldItemPicture->delete();
                        }

                        $file = $request->file("inventoryItem.$index.item_picture");
                        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $originalName = Str::slug($originalName);
                        $extension = $file->getClientOriginalExtension();

                        $fileName = "{$coopName}-{$inventory->id}-{$itemCategory}-{$itemName}-{$originalName}-{$date}.{$extension}";
                        $path = $file->storeAs('item_pictures', $fileName, 'public');

                        ItemPicturesFiles::updateOrCreate(
                            ['inventory_id' => $inventory->id],
                            [
                                'file_name' => $fileName,
                                'file_path' => $path,
                                'file_type' => $file->getClientMimeType(),
                            ]
                        );
                    }

                    if ($request->hasFile("inventoryItem.$index.moa_file")) {
                        $oldMoaFile = MoaFile::where('inventory_id', $inventory->id)->first();

                        if ($oldMoaFile && $oldMoaFile->file_path) {
                            Storage::disk('public')->delete($oldMoaFile->file_path);
                            $oldMoaFile->delete();
                        }

                        $file = $request->file("inventoryItem.$index.moa_file");
                        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $originalName = Str::slug($originalName);
                        $extension = $file->getClientOriginalExtension();

                        $fileName = "{$coopName}-{$inventory->id}-{$itemCategory}-{$itemName}-{$originalName}-{$date}.{$extension}";
                        $path = $file->storeAs('moa_files', $fileName, 'public');

                        MoaFile::updateOrCreate(
                            ['inventory_id' => $inventory->id],
                            [
                                'file_name' => $fileName,
                                'file_path' => $path,
                                'file_type' => $file->getClientMimeType(),
                            ]
                        );
                    }
                }
            }
        });

        return redirect()->route('admin.create')->with('success', 'Inventory saved successfully');
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
                    ->with([
                        'inventories.itemPictures',
                        'inventories.moaFiles',
                    ]);
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
            'instances.inventories.itemPictures',
            'instances.inventories.moaFiles',
        ])->findOrFail($id);

        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();

        $inventories = [];

        if ($cooperative->instances->isNotEmpty()) {
            foreach ($cooperative->instances->first()->inventories as $inv) {
                $itemPicture = $inv->itemPictures->first();
                $moaFile = $inv->moaFiles->first();

                $inventories[] = [
                    'id' => $inv->id,
                    'category' => $inv->category,
                    'name' => $inv->name,
                    'granting_agency' => $inv->granting_agency,
                    'location' => $inv->location,
                    'value' => $inv->value,
                    'quantity' => $inv->quantity,
                    'status' => $inv->status,
                    'acquired_date' => $inv->acquired_date,

                    'item_picture_meta' => $itemPicture ? [
                        'id' => $itemPicture->id,
                        'file_name' => $itemPicture->file_name,
                        'file_path' => $itemPicture->file_path,
                        'file_type' => $itemPicture->file_type,
                    ] : null,

                    'moa_file_meta' => $moaFile ? [
                        'id' => $moaFile->id,
                        'file_name' => $moaFile->file_name,
                        'file_path' => $moaFile->file_path,
                        'file_type' => $moaFile->file_type,
                    ] : null,

                    'item_picture' => null,
                    'moa_file' => null,
                    'name_search' => $inv->name,
                ];
            }
        }

        $inventoryNames = DB::table('inventories')
            ->selectRaw('MIN(id) as id, MIN(name) as name, category')
            ->groupBy('category', DB::raw('LOWER(TRIM(name))'))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $grantingAgencyNames = DB::table('inventories') // self should be constant option, not from db
            ->selectRaw('MIN(id) as id, MIN(granting_agency) as name')
            ->whereNotNull('granting_agency')
            ->whereRaw('TRIM(granting_agency) != ""')
            ->whereRaw('LOWER(TRIM(granting_agency)) != "self"')
            ->groupBy(DB::raw('LOWER(TRIM(granting_agency))'))
            ->orderBy('name')
            ->get();

        $grantingAgencyNames->prepend((object) ['id' => null, 'name' => 'Self']);
        
        return inertia('admin/Edit', [
            'cooperative' => $cooperative,
            'inventoryItem' => $inventories,
            'regions' => $regions,
            'provinces' => $provinces,
            'inventoryNames' => $inventoryNames,
            'cities' => $cities,
            'barangays' => $barangays,
            'grantingAgencyNames' => $grantingAgencyNames,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('admin.dashboard')],
                ['title' => 'Cooperative Details', 'href' => route('admin.dashboard.showdetails', $id)],
                ['title' => 'Edit', 'href' => route('admin.dashboard.edit', $id)],
            ]
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
            'inventoryItem.*.id' => 'nullable|integer|exists:inventories,id',
            'inventoryItem.*.category' => 'required|string',
            'inventoryItem.*.name' => 'required|string',
            'inventoryItem.*.granting_agency' => 'required|string',
            'inventoryItem.*.location' => 'required|string',
            'inventoryItem.*.value' => 'required|numeric',
            'inventoryItem.*.quantity' => 'required|integer',
            'inventoryItem.*.status' => 'nullable|integer',
            'inventoryItem.*.acquired_date' => 'required|date',

            'inventoryItem.*.item_picture' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'inventoryItem.*.moa_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        DB::transaction(function () use ($validated, $request, $id) {
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

            $reportingDate = ReportingDate::orderByDesc('reporting_year')
                ->orderByDesc('reporting_month')
                ->first();

            $instance = InventoryInstance::firstOrCreate([
                'coop_id' => $coop->id,
                'reporting_date_id' => $reportingDate ? $reportingDate->id : null,
            ]);

            $submittedIds = [];

            foreach ($validated['inventoryItem'] ?? [] as $index => $item) {
                $inventory = null;

                if (!empty($item['id'])) {
                    $inventory = Inventory::where('inventory_instance_id', $instance->id)
                        ->where('id', $item['id'])
                        ->first();
                }
                $normalizedItemName = $this->normalizeItemName($item['name']);
                if ($inventory) {
                    $inventory->update([
                        'category' => $item['category'],
                        'name' => $normalizedItemName,
                        'granting_agency' => $item['granting_agency'],
                        'location' => $item['location'],
                        'value' => $item['value'],
                        'quantity' => $item['quantity'],
                        'status' => $item['status'],
                        'acquired_date' => $item['acquired_date'],
                    ]);
                } else {
                    $inventory = Inventory::create([
                        'inventory_instance_id' => $instance->id,
                        'category' => $item['category'],
                        'name' => $normalizedItemName,
                        'granting_agency' => $item['granting_agency'],
                        'location' => $item['location'],
                        'value' => $item['value'],
                        'quantity' => $item['quantity'],
                        'status' => $item['status'],
                        'acquired_date' => $item['acquired_date'],
                    ]);
                }

                $submittedIds[] = $inventory->id;

                $date = now()->format('m-d-Y');
                $coopName = Str::slug($coop->name);
                $itemCategory = Str::slug($inventory->category);
                $itemName = Str::slug($inventory->name);

                // Replace item picture if new file uploaded
                if ($request->hasFile("inventoryItem.$index.item_picture")) {
                    $oldItemPicture = ItemPicturesFiles::where('inventory_id', $inventory->id)->first();

                    if ($oldItemPicture && $oldItemPicture->file_path) {
                        Storage::disk('public')->delete($oldItemPicture->file_path);
                        $oldItemPicture->delete();
                    }

                    $file = $request->file("inventoryItem.$index.item_picture");
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $originalName = Str::slug($originalName);
                    $extension = $file->getClientOriginalExtension();

                    $fileName = "{$coopName}-{$inventory->id}-{$itemCategory}-{$itemName}-{$originalName}-{$date}.{$extension}";
                    $path = $file->storeAs('item_pictures', $fileName, 'public');

                    ItemPicturesFiles::updateOrCreate(
                        ['inventory_id' => $inventory->id],
                        [
                            'file_name' => $fileName,
                            'file_path' => $path,
                            'file_type' => $file->getClientMimeType(),
                        ]
                    );
                }

                // Replace MOA file if new file uploaded
                if ($request->hasFile("inventoryItem.$index.moa_file")) {
                    $oldMoaFile = MoaFile::where('inventory_id', $inventory->id)->first();

                    if ($oldMoaFile && $oldMoaFile->file_path) {
                        Storage::disk('public')->delete($oldMoaFile->file_path);
                        $oldMoaFile->delete();
                    }

                    $file = $request->file("inventoryItem.$index.moa_file");
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $originalName = Str::slug($originalName);
                    $extension = $file->getClientOriginalExtension();

                    $fileName = "{$coopName}-{$inventory->id}-{$itemCategory}-{$itemName}-{$originalName}-{$date}.{$extension}";
                    $path = $file->storeAs('moa_files', $fileName, 'public');

                    MoaFile::updateOrCreate(
                        ['inventory_id' => $inventory->id],
                        [
                            'file_name' => $fileName,
                            'file_path' => $path,
                            'file_type' => $file->getClientMimeType(),
                        ]
                    );
                }
            }

            // delete inventories removed from form
            $toDelete = $instance->inventories()->whereNotIn('id', $submittedIds)->get();

            foreach ($toDelete as $inventory) {
                $oldItemPicture = ItemPicturesFiles::where('inventory_id', $inventory->id)->first();
                if ($oldItemPicture && $oldItemPicture->file_path) {
                    Storage::disk('public')->delete($oldItemPicture->file_path);
                    $oldItemPicture->delete();
                }

                $oldMoaFile = MoaFile::where('inventory_id', $inventory->id)->first();
                if ($oldMoaFile && $oldMoaFile->file_path) {
                    Storage::disk('public')->delete($oldMoaFile->file_path);
                    $oldMoaFile->delete();
                }

                $inventory->delete();
            }
        });

        return redirect()->route('admin.dashboard.showdetails', $id);
    }

    private function normalizeItemName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title(Str::lower($name));
    }
}
