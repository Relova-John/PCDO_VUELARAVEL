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
use App\Models\MoaFile;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemPicturesFiles;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $pendingCode = $request->session()->get('pending_access_code');

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

                $request->session()->forget('pending_access_code');
                
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

        $hasAccess = $user && ! is_null($user->locationAccesses());

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
                'assignedLocation' => [
                    'region_code' => null,
                    'province_code' => null,
                    'city_code' => null,
                    'barangay_code' => null,
                ],
                'regions' => [],
                'provinces' => [],
                'cities' => [],
                'barangays' => [],
                'inventorySummaryRows' => [],
                'categories' => [],
                'breadcrumbs' => [
                    ['title' => 'Cooperatives', 'href' => route('officer.dashboard')]
                ],
            ]);
        }

        $cooperativesQuery = Cooperative::query()->select('id', 'name', 'region_code', 'province_code', 'city_code', 'barangay_code');

        $this->applyLocationScope($cooperativesQuery, $user);

        $cooperatives = $cooperativesQuery
            ->orderBy('name')
            ->get();

        $coopIds = $cooperatives->pluck('id');

        $regions = Region::select('code', 'name')->orderBy('name')->get();
        $provinces = Province::select('code', 'name', 'region_code')->orderBy('name')->get();
        $cities = City::select('code', 'name', 'province_code')->orderBy('name')->get();
        $barangays = Barangay::select('code', 'name', 'city_code')->orderBy('name')->get();

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

        $inventorySummaryRows = DB::table('inventory_instances')
            ->join('inventories', 'inventory_instances.id', '=', 'inventories.inventory_instance_id')
            ->join('cooperatives', 'inventory_instances.coop_id', '=', 'cooperatives.id')
            ->leftJoin('regions', 'cooperatives.region_code', '=', 'regions.code')
            ->leftJoin('provinces', 'cooperatives.province_code', '=', 'provinces.code')
            ->leftJoin('cities', 'cooperatives.city_code', '=', 'cities.code')
            ->leftJoin('barangays', 'cooperatives.barangay_code', '=', 'barangays.code')
            ->where('inventory_instances.reporting_date_id', $reportingDateId)
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
            ->filter(fn($category) => filled($category))
            ->map(fn($category) => trim((string) $category))
            ->unique()
            ->sort()
            ->values()
            ->map(fn(string $category) => [
                'value' => $category,
                'label' => ucfirst($category),
            ])
            ->values();

        $locationAccesses = $this->getLocationAccesses($user);
        foreach ($locationAccesses as $access) {
            if ($access->region_code) {
                $access->location_name = Region::where('code', $access->region_code)->value('name');
            } elseif ($access->province_code) {
                $access->location_name = Province::where('code', $access->province_code)->value('name');
            } elseif ($access->city_code) {
                $access->location_name = City::where('code', $access->city_code)->value('name');
            } elseif ($access->barangay_code) {
                $access->location_name = Barangay::where('code', $access->barangay_code)->value('name');
            } else {
                $access->location_name = 'Unknown';
            }
        }

        return inertia('officer/Dashboard', [
            'locked' => false,
            'cooperatives' => $cooperatives,
            'inventoryCounts' => $inventoryCounts,
            'reportingDate' => $reportingDate,
            'reportingDates' => $reportingDates,
            'selectedReportingDate' => (int) $reportingDateId,
            'locationScope' => $this->buildLocationScope($user),
            'locationName' => $this->buildLocationName($user),
            // 'assignedLocation' => [
            //     'region_code' => $regionCode,
            //     'province_code' => $provinceCode,
            //     'city_code' => $cityCode,
            //     'barangay_code' => $barangayCode,
            // ],
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'inventorySummaryRows' => $inventorySummaryRows,
            'categories' => $categories,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('officer.dashboard')]
            ]
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

        if (! $user->access_control_id) {
            return redirect()
                ->route('officer.dashboard')
                ->withErrors([
                    'code' => 'You must activate an access code first.',
                ]);
        }

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
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'inventoryNames' => $inventoryNames,
            'grantingAgencyNames' => $grantingAgencyNames,

            'locationLock' => [
                'region_code' => $user->region_code,
                'province_code' => $user->province_code,
                'city_code' => $user->city_code,
                'barangay_code' => $user->barangay_code,
            ],
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

    private function getLocationAccesses($user)
    {
        return collect($user->locationAccesses ?? []);
    }

    private function applyLocationScope($query, $user): void
    {
        $accesses = $this->getLocationAccesses($user);

        $hasBarangay = $accesses->whereNotNull('barangay_code')->isNotEmpty();
        $hasCity = $accesses->whereNotNull('city_code')->isNotEmpty();
        $hasProvince = $accesses->whereNotNull('province_code')->isNotEmpty();
        $hasRegion = $accesses->whereNotNull('region_code')->isNotEmpty();

        if ($hasBarangay) {
            $barangayCodes = $accesses->whereNotNull('barangay_code')->pluck('barangay_code')->unique()->toArray();
            $query->whereIn('barangay_code', $barangayCodes);
        } elseif ($hasCity) {
            $cityCodes = $accesses->whereNotNull('city_code')->pluck('city_code')->unique()->toArray();
            $query->whereIn('city_code', $cityCodes);
        } elseif ($hasProvince) {
            $provinceCodes = $accesses->whereNotNull('province_code')->pluck('province_code')->unique()->toArray();
            $query->whereIn('province_code', $provinceCodes);
        } elseif ($hasRegion) {
            $regionCodes = $accesses->whereNotNull('region_code')->pluck('region_code')->unique()->toArray();
            $query->whereIn('region_code', $regionCodes);
        }
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
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'inventoryNames' => $inventoryNames,
            'grantingAgencyNames' => $grantingAgencyNames,
            'locationLock' => [
                'region_code' => $user->region_code,
                'province_code' => $user->province_code,
                'city_code' => $user->city_code,
                'barangay_code' => $user->barangay_code,
            ],
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

    private function applyLockedLocationValues(array $validated, $user): array
    {
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

    private function assertLocationMatchesScope(array $validated, $user): void
    {
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
    }
}
