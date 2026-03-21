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
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\ItemPicturesFiles;
use App\Models\MoaFile;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function index()
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

        return inertia('guest/Form', [
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'inventoryNames' => $inventoryNames,
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
                            'name' => $normalizedItemName
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

        return redirect()->route('guest.create')->with('success', 'Inventory saved successfully');
    }

    private function normalizeItemName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title(Str::lower($name));
    }
}
