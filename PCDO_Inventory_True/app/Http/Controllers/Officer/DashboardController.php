<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\AccessControl;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Cooperative;
use App\Models\Inventory;
use App\Models\InventoryInstance;
use App\Models\ItemPicturesFiles;
use App\Models\MoaFile;
use App\Models\Province;
use App\Models\Region;
use App\Models\ReportingDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $reportingDates = ReportingDate::query()
            ->orderByDesc('reporting_year')
            ->orderByDesc('reporting_month')
            ->get();

        if ($reportingDates->isEmpty()) {
            $reportingDate = ReportingDate::create([
                'reporting_year' => now()->year,
                'reporting_month' => now()->month,
            ]);

            $reportingDates = collect([$reportingDate]);
        }

        $reportingDateId = (int) ($request->reporting_date_id ?? $reportingDates->first()->id);
        $reportingDate = ReportingDate::find($reportingDateId);

        $locationOptions = $this->buildLocationOptions($user);
        $hasAccess = $this->hasLocationAccess($locationOptions);

        if (! $hasAccess) {
            return inertia('officer/Dashboard', [
                'locked' => true,
                'reportingDate' => $reportingDate,
                'reportingDates' => $reportingDates,
                'selectedReportingDate' => $reportingDateId,
                'cooperatives' => [],
                'inventoryCounts' => [],
                'locationScope' => null,
                'locationName' => null,
                'assignedLocation' => [
                    'region_code' => null,
                    'province_code' => null,
                    'city_code' => null,
                    'barangay_code' => null,
                ],
                'availableLocationCodes' => [
                    'region_codes' => [],
                    'province_codes' => [],
                    'city_codes' => [],
                    'barangay_codes' => [],
                ],
                'regions' => [],
                'provinces' => [],
                'cities' => [],
                'barangays' => [],
                'inventorySummaryRows' => [],
                'categories' => [],
                'breadcrumbs' => [
                    ['title' => 'Cooperatives', 'href' => route('officer.dashboard')],
                ],
            ]);
        }

        $selected = $this->resolveSelectedLocationFilters($request, $locationOptions);
        $datasets = $this->buildScopedLocationDatasets(
            $locationOptions,
            $selected['region_code'],
            $selected['province_code'],
            $selected['city_code']
        );

        $selected = $this->normalizeSelectedAgainstDatasets($selected, $datasets);

        $cooperativesQuery = Cooperative::query()
            ->select('id', 'name', 'region_code', 'province_code', 'city_code', 'barangay_code');

        $this->applyLocationScope($cooperativesQuery, $user);
        $this->applyResolvedLocationFilters(
            $cooperativesQuery,
            $selected['region_code'],
            $selected['province_code'],
            $selected['city_code'],
            $selected['barangay_code']
        );

        $cooperatives = $cooperativesQuery
            ->orderBy('name')
            ->get();

        $coopIds = $cooperatives->pluck('id');

        $inventoryCounts = DB::table('inventory_instances')
            ->join('inventories', 'inventory_instances.id', '=', 'inventories.inventory_instance_id')
            ->where('inventory_instances.reporting_date_id', $reportingDateId)
            ->when($coopIds->isNotEmpty(), fn ($query) => $query->whereIn('inventory_instances.coop_id', $coopIds))
            ->select(
                'inventory_instances.coop_id',
                DB::raw('COUNT(inventories.id) as count')
            )
            ->groupBy('inventory_instances.coop_id')
            ->pluck('count', 'coop_id');

        $inventorySummaryRows = DB::table('inventory_instances')
            ->join('inventories', 'inventory_instances.id', '=', 'inventories.inventory_instance_id')
            ->join('cooperatives', 'inventory_instances.coop_id', '=', 'cooperatives.id')
            ->leftJoin('regions', 'cooperatives.region_code', '=', 'regions.code')
            ->leftJoin('provinces', 'cooperatives.province_code', '=', 'provinces.code')
            ->leftJoin('cities', 'cooperatives.city_code', '=', 'cities.code')
            ->leftJoin('barangays', 'cooperatives.barangay_code', '=', 'barangays.code')
            ->where('inventory_instances.reporting_date_id', $reportingDateId)
            ->when($coopIds->isNotEmpty(), fn ($query) => $query->whereIn('inventory_instances.coop_id', $coopIds))
            ->select(
                'inventories.id',
                'inventories.name',
                'inventories.category',
                'inventories.location as item_location',
                'inventories.value',
                'inventories.quantity',
                'inventories.status',
                'cooperatives.id as coop_id',
                'cooperatives.name as coop_name',
                'cooperatives.region_code',
                'cooperatives.province_code',
                'cooperatives.city_code',
                'cooperatives.barangay_code',
                'regions.name as region_name',
                'provinces.name as province_name',
                'cities.name as city_name',
                'barangays.name as barangay_name'
            )
            ->orderBy('inventories.category')
            ->orderBy('inventories.name')
            ->orderBy('cooperatives.name')
            ->get()
            ->map(function ($row) {
                $quantity = (int) ($row->quantity ?? 0);
                $serviceable = max(0, (int) ($row->status ?? 0));
                $unserviceable = max(0, $quantity - $serviceable);
                $amount = (float) ($row->value ?? 0);
                $total = $amount * $quantity;

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'category' => $row->category,
                    'item_location' => $row->item_location,
                    'value' => $amount,
                    'quantity' => $quantity,
                    'serviceable' => $serviceable,
                    'unserviceable' => $unserviceable,
                    'status_raw' => $row->status,
                    'coop_id' => $row->coop_id,
                    'coop_name' => $row->coop_name,
                    'region_code' => $row->region_code,
                    'province_code' => $row->province_code,
                    'city_code' => $row->city_code,
                    'barangay_code' => $row->barangay_code,
                    'region_name' => $row->region_name,
                    'province_name' => $row->province_name,
                    'city_name' => $row->city_name,
                    'barangay_name' => $row->barangay_name,
                    'coop_location' => collect([
                        $row->barangay_name,
                        $row->city_name,
                        $row->province_name,
                        $row->region_name,
                    ])->filter()->implode(', '),
                    'total' => $total,
                ];
            })
            ->values();

        $categories = $inventorySummaryRows
            ->pluck('category')
            ->filter(fn ($category) => filled($category))
            ->map(fn ($category) => trim((string) $category))
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $category) => [
                'value' => $category,
                'label' => ucfirst($category),
            ])
            ->values();

        return inertia('officer/Dashboard', [
            'locked' => false,
            'cooperatives' => $cooperatives,
            'inventoryCounts' => $inventoryCounts,
            'reportingDate' => $reportingDate,
            'reportingDates' => $reportingDates,
            'selectedReportingDate' => $reportingDateId,
            'locationScope' => $this->buildLocationScope($user),
            'locationName' => $this->buildLocationName($user),
            'assignedLocation' => $selected,
            'availableLocationCodes' => $locationOptions,
            'regions' => $datasets['regions'],
            'provinces' => $datasets['provinces'],
            'cities' => $datasets['cities'],
            'barangays' => $datasets['barangays'],
            'inventorySummaryRows' => $inventorySummaryRows,
            'categories' => $categories,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('officer.dashboard')],
            ],
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

    public function create(Request $request)
    {
        $user = $request->user();

        $locationOptions = $this->buildLocationOptions($user);
        $selected = $this->resolveSelectedLocationFilters($request, $locationOptions);
        $datasets = $this->buildScopedLocationDatasets(
            $locationOptions,
            $selected['region_code'],
            $selected['province_code'],
            $selected['city_code']
        );
        $selected = $this->normalizeSelectedAgainstDatasets($selected, $datasets);

        $inventoryNames = DB::table('inventories')
            ->selectRaw('MIN(id) as id, MIN(name) as name, category')
            ->groupBy('category', DB::raw('LOWER(TRIM(name))'))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $grantingAgencyNames = DB::table('inventories')
            ->selectRaw('MIN(id) as id, MIN(granting_agency) as name')
            ->whereNotNull('granting_agency')
            ->whereRaw('TRIM(granting_agency) != ""')
            ->whereRaw('LOWER(TRIM(granting_agency)) != "self"')
            ->groupBy(DB::raw('LOWER(TRIM(granting_agency))'))
            ->orderBy('name')
            ->get();

        $grantingAgencyNames->prepend((object) ['id' => null, 'name' => 'Self']);

        return inertia('officer/Form', [
            'regions' => $datasets['regions'],
            'provinces' => $datasets['provinces'],
            'cities' => $datasets['cities'],
            'barangays' => $datasets['barangays'],
            'inventoryNames' => $inventoryNames,
            'grantingAgencyNames' => $grantingAgencyNames,
            'allowedLocations' => $this->getAllowedLocationsForForm($user),
            'locationLock' => $this->buildLocationLock($user),
            'availableLocationCodes' => $locationOptions,
            'assignedLocation' => $selected,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->access_control_id) {
            return redirect()
                ->route('officer.dashboard')
                ->withErrors([
                    'code' => 'You must activate an access code first.',
                ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_code' => 'required|exists:regions,code',
            'province_code' => 'required|exists:provinces,code',
            'city_code' => 'required|exists:cities,code',
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

        $validated = $this->applyLockedLocationValues($validated, $user);
        $this->assertLocationMatchesScope($validated, $user);

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
                    $inventory = Inventory::updateOrCreate(
                        [
                            'inventory_instance_id' => $inventoryInstance->id,
                            'category' => $item['category'],
                            'name' => $item['name'],
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

        return redirect()->route('officer.create')->with('success', 'Inventory saved successfully');
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
                        ->with([
                            'inventories.itemPictures',
                            'inventories.moaFiles',
                        ]);
                },
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

    public function edit(Request $request, $id)
    {
        $user = $request->user();

        if (! $user->access_control_id) {
            return redirect()
                ->route('officer.dashboard')
                ->withErrors([
                    'code' => 'You must activate an access code first.',
                ]);
        }

        $cooperativeQuery = Cooperative::with([
            'instances.inventories.itemPictures',
            'instances.inventories.moaFiles',
        ]);

        $this->applyLocationScope($cooperativeQuery, $user);

        $cooperative = $cooperativeQuery->findOrFail($id);

        $locationOptions = $this->buildLocationOptions($user);

        $selected = [
            'region_code' => $cooperative->region_code,
            'province_code' => $cooperative->province_code,
            'city_code' => $cooperative->city_code,
            'barangay_code' => $cooperative->barangay_code,
        ];

        $selected = $this->resolveSelectedLocationFilters($request, $locationOptions, $selected);

        $datasets = $this->buildScopedLocationDatasets(
            $locationOptions,
            $selected['region_code'],
            $selected['province_code'],
            $selected['city_code']
        );

        $selected = $this->normalizeSelectedAgainstDatasets($selected, $datasets);

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

        $grantingAgencyNames = DB::table('inventories')
            ->selectRaw('MIN(id) as id, MIN(granting_agency) as name')
            ->whereNotNull('granting_agency')
            ->whereRaw('TRIM(granting_agency) != ""')
            ->whereRaw('LOWER(TRIM(granting_agency)) != "self"')
            ->groupBy(DB::raw('LOWER(TRIM(granting_agency))'))
            ->orderBy('name')
            ->get();

        $grantingAgencyNames->prepend((object) ['id' => null, 'name' => 'Self']);

        return inertia('officer/Edit', [
            'cooperative' => $cooperative,
            'inventoryItem' => $inventories,
            'regions' => $datasets['regions'],
            'provinces' => $datasets['provinces'],
            'cities' => $datasets['cities'],
            'barangays' => $datasets['barangays'],
            'inventoryNames' => $inventoryNames,
            'grantingAgencyNames' => $grantingAgencyNames,
            'allowedLocations' => $this->getAllowedLocationsForForm($user),
            'locationLock' => $this->buildLocationLock($user),
            'availableLocationCodes' => $locationOptions,
            'assignedLocation' => $selected,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('officer.dashboard')],
                ['title' => $cooperative->name, 'href' => route('officer.dashboard.showdetails', $cooperative->id)],
                ['title' => 'Edit', 'href' => ''],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (! $user->access_control_id) {
            return redirect()
                ->route('officer.dashboard')
                ->withErrors([
                    'code' => 'You must activate an access code first.',
                ]);
        }

        $cooperativeScopeQuery = Cooperative::query();
        $this->applyLocationScope($cooperativeScopeQuery, $user);
        $cooperativeScopeQuery->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_code' => 'required|exists:regions,code',
            'province_code' => 'required|exists:provinces,code',
            'city_code' => 'required|exists:cities,code',
            'barangay_code' => 'required|exists:barangays,code',
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

        $validated = $this->applyLockedLocationValues($validated, $user);
        $this->assertLocationMatchesScope($validated, $user);

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
                'reporting_date_id' => $reportingDate?->id,
            ]);

            $submittedIds = [];

            foreach ($validated['inventoryItem'] ?? [] as $index => $item) {
                $inventory = null;

                if (! empty($item['id'])) {
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

        return redirect()->route('officer.dashboard.showdetails', $id);
    }

    private function normalizeItemName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title(Str::lower($name));
    }

    private function getLocationAccesses($user)
    {
        return collect($user->locationAccesses ?? []);
    }

    private function hasLocationAccess(array $locationOptions): bool
    {
        return ! empty($locationOptions['region_codes'])
            || ! empty($locationOptions['province_codes'])
            || ! empty($locationOptions['city_codes'])
            || ! empty($locationOptions['barangay_codes']);
    }

    private function applyLocationScope($query, $user): void
    {
        $accesses = $this->getLocationAccesses($user);

        $hasBarangay = $accesses->whereNotNull('barangay_code')->isNotEmpty();
        $hasCity = $accesses->whereNotNull('city_code')->isNotEmpty();
        $hasProvince = $accesses->whereNotNull('province_code')->isNotEmpty();
        $hasRegion = $accesses->whereNotNull('region_code')->isNotEmpty();

        if ($hasBarangay) {
            $barangayCodes = $accesses->whereNotNull('barangay_code')->pluck('barangay_code')->unique()->values()->all();
            $query->whereIn('barangay_code', $barangayCodes);
        } elseif ($hasCity) {
            $cityCodes = $accesses->whereNotNull('city_code')->pluck('city_code')->unique()->values()->all();
            $query->whereIn('city_code', $cityCodes);
        } elseif ($hasProvince) {
            $provinceCodes = $accesses->whereNotNull('province_code')->pluck('province_code')->unique()->values()->all();
            $query->whereIn('province_code', $provinceCodes);
        } elseif ($hasRegion) {
            $regionCodes = $accesses->whereNotNull('region_code')->pluck('region_code')->unique()->values()->all();
            $query->whereIn('region_code', $regionCodes);
        }
    }

    private function applyResolvedLocationFilters(
        $query,
        ?string $regionCode,
        ?string $provinceCode,
        ?string $cityCode,
        ?string $barangayCode
    ): void {
        $query
            ->when($regionCode, fn ($q) => $q->where('region_code', $regionCode))
            ->when($provinceCode, fn ($q) => $q->where('province_code', $provinceCode))
            ->when($cityCode, fn ($q) => $q->where('city_code', $cityCode))
            ->when($barangayCode, fn ($q) => $q->where('barangay_code', $barangayCode));
    }

    private function buildLocationOptions($user): array
    {
        $accesses = $this->getLocationAccesses($user);

        if ($accesses->isEmpty()) {
            return [
                'region_codes' => [],
                'province_codes' => [],
                'city_codes' => [],
                'barangay_codes' => [],
            ];
        }

        $barangayCodes = $accesses->pluck('barangay_code')->filter()->map(fn ($code) => (string) $code)->unique()->values();
        $cityCodes = $accesses->pluck('city_code')->filter()->map(fn ($code) => (string) $code)->unique()->values();
        $provinceCodes = $accesses->pluck('province_code')->filter()->map(fn ($code) => (string) $code)->unique()->values();
        $regionCodes = $accesses->pluck('region_code')->filter()->map(fn ($code) => (string) $code)->unique()->values();

        if ($barangayCodes->isNotEmpty()) {
            $barangays = Barangay::query()
                ->select('code', 'city_code')
                ->whereIn('code', $barangayCodes->all())
                ->get();

            $cities = City::query()
                ->select('code', 'province_code', 'region_code')
                ->whereIn('code', $barangays->pluck('city_code')->filter()->unique()->values()->all())
                ->get();

            $provinces = Province::query()
                ->select('code', 'region_code')
                ->whereIn('code', $cities->pluck('province_code')->filter()->unique()->values()->all())
                ->get();

            return [
                'region_codes' => $provinces->pluck('region_code')->filter()->unique()->values()->all(),
                'province_codes' => $provinces->pluck('code')->filter()->unique()->values()->all(),
                'city_codes' => $cities->pluck('code')->filter()->unique()->values()->all(),
                'barangay_codes' => $barangays->pluck('code')->filter()->unique()->values()->all(),
            ];
        }

        if ($cityCodes->isNotEmpty()) {
            $cities = City::query()
                ->select('code', 'province_code', 'region_code')
                ->whereIn('code', $cityCodes->all())
                ->get();

            $provinces = Province::query()
                ->select('code', 'region_code')
                ->whereIn('code', $cities->pluck('province_code')->filter()->unique()->values()->all())
                ->get();

            $barangays = Barangay::query()
                ->select('code', 'city_code')
                ->whereIn('city_code', $cities->pluck('code')->filter()->unique()->values()->all())
                ->get();

            return [
                'region_codes' => $provinces->pluck('region_code')->filter()->unique()->values()->all(),
                'province_codes' => $provinces->pluck('code')->filter()->unique()->values()->all(),
                'city_codes' => $cities->pluck('code')->filter()->unique()->values()->all(),
                'barangay_codes' => $barangays->pluck('code')->filter()->unique()->values()->all(),
            ];
        }

        if ($provinceCodes->isNotEmpty()) {
            $provinces = Province::query()
                ->select('code', 'region_code')
                ->whereIn('code', $provinceCodes->all())
                ->get();

            $cities = City::query()
                ->select('code', 'province_code', 'region_code')
                ->whereIn('province_code', $provinces->pluck('code')->filter()->unique()->values()->all())
                ->get();

            $barangays = Barangay::query()
                ->select('code', 'city_code')
                ->whereIn('city_code', $cities->pluck('code')->filter()->unique()->values()->all())
                ->get();

            return [
                'region_codes' => $provinces->pluck('region_code')->filter()->unique()->values()->all(),
                'province_codes' => $provinces->pluck('code')->filter()->unique()->values()->all(),
                'city_codes' => $cities->pluck('code')->filter()->unique()->values()->all(),
                'barangay_codes' => $barangays->pluck('code')->filter()->unique()->values()->all(),
            ];
        }

        if ($regionCodes->isNotEmpty()) {
            $provinces = Province::query()
                ->select('code', 'region_code')
                ->whereIn('region_code', $regionCodes->all())
                ->get();

            $cities = City::query()
                ->select('code', 'province_code', 'region_code')
                ->whereIn('province_code', $provinces->pluck('code')->filter()->unique()->values()->all())
                ->get();

            $barangays = Barangay::query()
                ->select('code', 'city_code')
                ->whereIn('city_code', $cities->pluck('code')->filter()->unique()->values()->all())
                ->get();

            return [
                'region_codes' => $regionCodes->values()->all(),
                'province_codes' => $provinces->pluck('code')->filter()->unique()->values()->all(),
                'city_codes' => $cities->pluck('code')->filter()->unique()->values()->all(),
                'barangay_codes' => $barangays->pluck('code')->filter()->unique()->values()->all(),
            ];
        }

        return [
            'region_codes' => [],
            'province_codes' => [],
            'city_codes' => [],
            'barangay_codes' => [],
        ];
    }

    private function resolveSelectedLocationFilters(
        Request $request,
        array $locationOptions,
        array $defaults = []
    ): array {
        $selected = [
            'region_code' => $request->filled('region_code')
                ? (string) $request->string('region_code')
                : ($defaults['region_code'] ?? (count($locationOptions['region_codes']) === 1 ? $locationOptions['region_codes'][0] : null)),
            'province_code' => $request->filled('province_code')
                ? (string) $request->string('province_code')
                : ($defaults['province_code'] ?? (count($locationOptions['province_codes']) === 1 ? $locationOptions['province_codes'][0] : null)),
            'city_code' => $request->filled('city_code')
                ? (string) $request->string('city_code')
                : ($defaults['city_code'] ?? (count($locationOptions['city_codes']) === 1 ? $locationOptions['city_codes'][0] : null)),
            'barangay_code' => $request->filled('barangay_code')
                ? (string) $request->string('barangay_code')
                : ($defaults['barangay_code'] ?? (count($locationOptions['barangay_codes']) === 1 ? $locationOptions['barangay_codes'][0] : null)),
        ];

        foreach (['region', 'province', 'city', 'barangay'] as $level) {
            $key = "{$level}_code";
            $allowedKey = "{$level}_codes";

            if ($selected[$key] && ! in_array($selected[$key], $locationOptions[$allowedKey], true)) {
                $selected[$key] = null;
            }
        }

        return $selected;
    }

    private function buildScopedLocationDatasets(
        array $locationOptions,
        ?string $selectedRegionCode = null,
        ?string $selectedProvinceCode = null,
        ?string $selectedCityCode = null
    ): array {
        $regions = Region::query()
            ->select('code', 'name')
            ->whereIn('code', $locationOptions['region_codes'])
            ->orderBy('name')
            ->get();

        $provinces = Province::query()
            ->select('code', 'name', 'region_code')
            ->whereIn('code', $locationOptions['province_codes'])
            ->when($selectedRegionCode, fn ($query) => $query->where('region_code', $selectedRegionCode))
            ->orderBy('name')
            ->get();

        if (
            $selectedProvinceCode &&
            ! $provinces->pluck('code')->contains($selectedProvinceCode)
        ) {
            $selectedProvinceCode = null;
            $selectedCityCode = null;
        }

        $cities = City::query()
            ->select('code', 'name', 'province_code', 'region_code')
            ->whereIn('code', $locationOptions['city_codes'])
            ->when($selectedRegionCode, fn ($query) => $query->where('region_code', $selectedRegionCode))
            ->when($selectedProvinceCode, fn ($query) => $query->where('province_code', $selectedProvinceCode))
            ->orderBy('name')
            ->get();

        if (
            $selectedCityCode &&
            ! $cities->pluck('code')->contains($selectedCityCode)
        ) {
            $selectedCityCode = null;
        }

        $barangays = Barangay::query()
            ->select('code', 'name', 'city_code')
            ->whereIn('code', $locationOptions['barangay_codes'])
            ->when($selectedCityCode, fn ($query) => $query->where('city_code', $selectedCityCode))
            ->orderBy('name')
            ->get();

        return [
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
        ];
    }

    private function normalizeSelectedAgainstDatasets(array $selected, array $datasets): array
    {
        if (
            $selected['province_code'] &&
            ! $datasets['provinces']->pluck('code')->contains($selected['province_code'])
        ) {
            $selected['province_code'] = null;
            $selected['city_code'] = null;
            $selected['barangay_code'] = null;
        }

        if (
            $selected['city_code'] &&
            ! $datasets['cities']->pluck('code')->contains($selected['city_code'])
        ) {
            $selected['city_code'] = null;
            $selected['barangay_code'] = null;
        }

        if (
            $selected['barangay_code'] &&
            ! $datasets['barangays']->pluck('code')->contains($selected['barangay_code'])
        ) {
            $selected['barangay_code'] = null;
        }

        return $selected;
    }

    private function buildLocationScope($user): ?string
    {
        $accesses = $this->getLocationAccesses($user);

        if ($accesses->whereNotNull('barangay_code')->isNotEmpty()) {
            $count = $accesses->whereNotNull('barangay_code')->pluck('barangay_code')->unique()->count();
            return 'Barangay Scope' . ($count > 1 ? " ({$count})" : '');
        }

        if ($accesses->whereNotNull('city_code')->isNotEmpty()) {
            $count = $accesses->whereNotNull('city_code')->pluck('city_code')->unique()->count();
            return 'City / Municipality Scope' . ($count > 1 ? " ({$count})" : '');
        }

        if ($accesses->whereNotNull('province_code')->isNotEmpty()) {
            $count = $accesses->whereNotNull('province_code')->pluck('province_code')->unique()->count();
            return 'Province Scope' . ($count > 1 ? " ({$count})" : '');
        }

        if ($accesses->whereNotNull('region_code')->isNotEmpty()) {
            $count = $accesses->whereNotNull('region_code')->pluck('region_code')->unique()->count();
            return 'Region Scope' . ($count > 1 ? " ({$count})" : '');
        }

        return null;
    }

    private function buildLocationName($user): ?string
    {
        $accesses = $this->getLocationAccesses($user);

        $barangayCodes = $accesses->whereNotNull('barangay_code')->pluck('barangay_code')->unique()->values()->all();
        if (! empty($barangayCodes)) {
            return Barangay::whereIn('code', $barangayCodes)
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->implode(', ');
        }

        $cityCodes = $accesses->whereNotNull('city_code')->pluck('city_code')->unique()->values()->all();
        if (! empty($cityCodes)) {
            return City::whereIn('code', $cityCodes)
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->implode(', ');
        }

        $provinceCodes = $accesses->whereNotNull('province_code')->pluck('province_code')->unique()->values()->all();
        if (! empty($provinceCodes)) {
            return Province::whereIn('code', $provinceCodes)
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->implode(', ');
        }

        $regionCodes = $accesses->whereNotNull('region_code')->pluck('region_code')->unique()->values()->all();
        if (! empty($regionCodes)) {
            return Region::whereIn('code', $regionCodes)
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->implode(', ');
        }

        return null;
    }

    private function applyLockedLocationValues(array $validated, $user): array
    {
        $locationAccesses = collect($user->locationAccesses ?? []);

        if ($user->region_code || $user->province_code || $user->city_code || $user->barangay_code) {
            if ($user->region_code) {
                $validated['region_code'] = $user->region_code;
            }

            if ($user->province_code) {
                $validated['province_code'] = $user->province_code;
            }

            if ($user->city_code) {
                $validated['city_code'] = $user->city_code;
            }

            if ($user->barangay_code) {
                $validated['barangay_code'] = $user->barangay_code;
            }

            return $validated;
        }

        if ($locationAccesses->isEmpty()) {
            return $validated;
        }

        $locationOptions = $this->buildLocationOptions($user);

        if (count($locationOptions['region_codes']) === 1) {
            $validated['region_code'] = $locationOptions['region_codes'][0];
        }

        if (count($locationOptions['province_codes']) === 1) {
            $validated['province_code'] = $locationOptions['province_codes'][0];
        }

        if (count($locationOptions['city_codes']) === 1) {
            $validated['city_code'] = $locationOptions['city_codes'][0];
        }

        if (count($locationOptions['barangay_codes']) === 1) {
            $validated['barangay_code'] = $locationOptions['barangay_codes'][0];
        }

        return $validated;
    }

    private function assertLocationMatchesScope(array $validated, $user): void
    {
        $locationAccesses = collect($user->locationAccesses ?? []);

        if ($user->region_code || $user->province_code || $user->city_code || $user->barangay_code) {
            if ($user->region_code && $validated['region_code'] !== $user->region_code) {
                abort(403, 'You cannot create inventory outside your assigned region.');
            }

            if ($user->province_code && $validated['province_code'] !== $user->province_code) {
                abort(403, 'You cannot create inventory outside your assigned province.');
            }

            if ($user->city_code && $validated['city_code'] !== $user->city_code) {
                abort(403, 'You cannot create inventory outside your assigned city.');
            }

            if ($user->barangay_code && $validated['barangay_code'] !== $user->barangay_code) {
                abort(403, 'You cannot create inventory outside your assigned barangay.');
            }

            return;
        }

        if ($locationAccesses->isEmpty()) {
            abort(403, 'You have no location access assigned.');
        }

        $hasBarangay = $locationAccesses->whereNotNull('barangay_code')->isNotEmpty();
        $hasCity = $locationAccesses->whereNotNull('city_code')->isNotEmpty();
        $hasProvince = $locationAccesses->whereNotNull('province_code')->isNotEmpty();
        $hasRegion = $locationAccesses->whereNotNull('region_code')->isNotEmpty();

        if ($hasBarangay) {
            $allowedCodes = $locationAccesses->whereNotNull('barangay_code')->pluck('barangay_code')->unique()->values()->all();
            if (! in_array($validated['barangay_code'] ?? null, $allowedCodes, true)) {
                abort(403, 'The selected barangay is not within your allowed scope.');
            }
        } elseif ($hasCity) {
            $allowedCodes = $locationAccesses->whereNotNull('city_code')->pluck('city_code')->unique()->values()->all();
            if (! in_array($validated['city_code'] ?? null, $allowedCodes, true)) {
                abort(403, 'The selected city is not within your allowed scope.');
            }
        } elseif ($hasProvince) {
            $allowedCodes = $locationAccesses->whereNotNull('province_code')->pluck('province_code')->unique()->values()->all();
            if (! in_array($validated['province_code'] ?? null, $allowedCodes, true)) {
                abort(403, 'The selected province is not within your allowed scope.');
            }
        } elseif ($hasRegion) {
            $allowedCodes = $locationAccesses->whereNotNull('region_code')->pluck('region_code')->unique()->values()->all();
            if (! in_array($validated['region_code'] ?? null, $allowedCodes, true)) {
                abort(403, 'The selected region is not within your allowed scope.');
            }
        }
    }

    private function getAllowedLocationsForForm($user): array
    {
        $locationAccesses = collect($user->locationAccesses ?? []);

        if ($locationAccesses->isEmpty()) {
            return [
                'level' => null,
                'items' => [],
            ];
        }

        $hasBarangay = $locationAccesses->whereNotNull('barangay_code')->isNotEmpty();
        $hasCity = $locationAccesses->whereNotNull('city_code')->isNotEmpty();
        $hasProvince = $locationAccesses->whereNotNull('province_code')->isNotEmpty();
        $hasRegion = $locationAccesses->whereNotNull('region_code')->isNotEmpty();

        if ($hasBarangay) {
            $codes = $locationAccesses->whereNotNull('barangay_code')->pluck('barangay_code')->unique();
            $items = Barangay::whereIn('code', $codes)
                ->with('city.province.region')
                ->get()
                ->map(function ($barangay) {
                    return [
                        'code' => $barangay->code,
                        'name' => $barangay->name,
                        'city_code' => $barangay->city_code,
                        'province_code' => $barangay->city->province_code ?? null,
                        'region_code' => $barangay->city->province->region_code ?? null,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'level' => 'barangay',
                'items' => $items,
            ];
        }

        if ($hasCity) {
            $codes = $locationAccesses->whereNotNull('city_code')->pluck('city_code')->unique();
            $items = City::whereIn('code', $codes)
                ->with('province.region')
                ->get()
                ->map(function ($city) {
                    return [
                        'code' => $city->code,
                        'name' => $city->name,
                        'province_code' => $city->province_code,
                        'region_code' => $city->province->region_code ?? null,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'level' => 'city',
                'items' => $items,
            ];
        }

        if ($hasProvince) {
            $codes = $locationAccesses->whereNotNull('province_code')->pluck('province_code')->unique();
            $items = Province::whereIn('code', $codes)
                ->with('region')
                ->get()
                ->map(function ($province) {
                    return [
                        'code' => $province->code,
                        'name' => $province->name,
                        'region_code' => $province->region_code,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'level' => 'province',
                'items' => $items,
            ];
        }

        if ($hasRegion) {
            $codes = $locationAccesses->whereNotNull('region_code')->pluck('region_code')->unique();
            $items = Region::whereIn('code', $codes)
                ->get()
                ->map(function ($region) {
                    return [
                        'code' => $region->code,
                        'name' => $region->name,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'level' => 'region',
                'items' => $items,
            ];
        }

        return [
            'level' => null,
            'items' => [],
        ];
    }

    private function buildLocationLock($user): array
    {
        $locationAccesses = collect($user->locationAccesses ?? []);

        $lock = [
            'region_code' => $user->region_code,
            'province_code' => $user->province_code,
            'city_code' => $user->city_code,
            'barangay_code' => $user->barangay_code,
        ];

        if ($locationAccesses->isNotEmpty() && ! ($user->region_code || $user->province_code || $user->city_code || $user->barangay_code)) {
            $locationOptions = $this->buildLocationOptions($user);

            if (count($locationOptions['barangay_codes']) === 1) {
                $barangay = Barangay::where('code', $locationOptions['barangay_codes'][0])
                    ->with('city.province.region')
                    ->first();

                if ($barangay) {
                    $lock['barangay_code'] = $barangay->code;
                    $lock['city_code'] = $barangay->city_code;
                    $lock['province_code'] = $barangay->city->province_code ?? null;
                    $lock['region_code'] = $barangay->city->province->region_code ?? null;
                }
            } elseif (count($locationOptions['city_codes']) === 1) {
                $city = City::where('code', $locationOptions['city_codes'][0])
                    ->with('province.region')
                    ->first();

                if ($city) {
                    $lock['city_code'] = $city->code;
                    $lock['province_code'] = $city->province_code;
                    $lock['region_code'] = $city->province->region_code ?? null;
                }
            } elseif (count($locationOptions['province_codes']) === 1) {
                $province = Province::where('code', $locationOptions['province_codes'][0])
                    ->with('region')
                    ->first();

                if ($province) {
                    $lock['province_code'] = $province->code;
                    $lock['region_code'] = $province->region_code;
                }
            } elseif (count($locationOptions['region_codes']) === 1) {
                $lock['region_code'] = $locationOptions['region_codes'][0];
            }
        }

        return $lock;
    }
}