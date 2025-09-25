<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\CoopDetail;
use App\Models\Cooperative;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Options as CsvReaderOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Options as CsvWriterOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class CooperativesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cooperatives = Cooperative::with('details')
            ->withCount(['programs as ongoing_program_count' => function ($q) {
                $q->where('program_status', 'ongoing');
            }])
            ->orderByDesc('ongoing_program_count')
            ->orderBy('id')
            ->get()
            ->map(function ($coop) {
                return [
                    'id' => $coop->id,
                    'name' => $coop->name,
                    'type' => $coop->type,
                    'holder' => $coop->holder,
                    'member_count' => $coop->details->members_count ?? 0,
                    'has_ongoing_program' => $coop->ongoing_program_count > 0,
                ];
            });

        return inertia('cooperatives/index', [
            'cooperatives' => $cooperatives,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cooperatives = Cooperative::orderBy('name')->get(['id', 'name']);
        $regions = Region::orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name', 'region_id']);
        $municipalities = Municipality::orderBy('name')->get(['id', 'name', 'province_id']);
        $barangays = Barangay::orderBy('name')->get(['id', 'name', 'municipality_id']);

        return inertia('cooperatives/create', [
            'cooperatives' => $cooperatives,
            'regions' => $regions,
            'provinces' => $provinces,
            'municipalities' => $municipalities,
            'barangays' => $barangays,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => 'Create Cooperative', 'href' => route('cooperatives.create')],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string|max:255|unique:cooperatives,id',
            'name' => 'required|string|max:255|unique:cooperatives,name',
            'region_id' => 'required|integer',
            'province_id' => 'required|integer',
            'municipality_id' => 'required|integer',
            'barangay_id' => 'required|integer',
            'asset_size' => 'required|string',
            'coop_type' => 'required|string',
            'status_category' => 'required|string',
            'bond_of_membership' => 'required|string',
            'area_of_operation' => 'required|string',
            'citizenship' => 'required|string',
            'members_count' => 'required|integer',
            'total_asset' => 'required|numeric',
            'net_surplus' => 'required|numeric',
        ]);

        $cooperative = Cooperative::where('name', $data['name'])->first();
        if ($cooperative) {
            return redirect()
                ->route('cooperatives.show', $cooperative)
                ->with('info', 'Cooperative already exists. Redirected to its details page.');
        }

        $cooperative = Cooperative::create([
            'id' => $data['id'],
            'name' => $data['name'],
        ]);

        $detailsData = [
            'coop_id' => $cooperative->id,
            'region_id' => $data['region_id'],
            'province_id' => $data['province_id'],
            'municipality_id' => $data['municipality_id'],
            'barangay_id' => $data['barangay_id'],
            'asset_size' => $data['asset_size'],
            'coop_type' => $data['coop_type'],
            'status_category' => $data['status_category'],
            'bond_of_membership' => $data['bond_of_membership'],
            'area_of_operation' => $data['area_of_operation'],
            'citizenship' => $data['citizenship'],
            'members_count' => $data['members_count'],
            'total_asset' => $data['total_asset'],
            'net_surplus' => $data['net_surplus'],
        ];
        CoopDetail::create($detailsData);

        return redirect()
            ->route('cooperatives.show', $cooperative)
            ->with('success', 'Cooperative created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cooperative $cooperative)
    {
        $cooperative->load('details');

        return inertia('cooperatives/show', [
            'cooperative' => $cooperative,
            'details' => $cooperative->details,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => $cooperative->name, 'href' => route('cooperatives.show', $cooperative)],
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cooperative $cooperative)
    {
        return inertia('cooperatives/edit', [
            'cooperative' => $cooperative,
            'details' => $cooperative->details,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => $cooperative->name, 'href' => route('cooperatives.show', $cooperative)],
                ['title' => 'Edit Cooperative', 'href' => route('cooperatives.edit', $cooperative)],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cooperative $cooperative)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:cooperatives,name,'.$cooperative->id,
            'holder' => 'nullable|string|max:255',
            'type' => 'required|in:primary,secondary,tertiary',
        ]);

        $cooperative->update($data);

        return redirect()
            ->route('cooperatives.index')
            ->with('success', 'Cooperative updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cooperative $cooperative)
    {
        try {
            $cooperative->delete();

            return redirect()
                ->route('cooperatives.index')
                ->with('success', 'Cooperative deleted successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->route('cooperatives.index')
                ->with('error', "Something went wrong while deleting.\nError: {$e->getMessage()}");
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $type = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($type, ['csv', 'xlsx'])) {
            return redirect()
                ->route('cooperatives.index')
                ->with('error', 'The file type is not supported.');
        }

        if ($type === 'csv') {
            $options = new CsvReaderOptions;
            $options->FIELD_DELIMITER = '|';
            $reader = new CsvReader($options);
        } else {
            $reader = new XlsxReader;
        }
        $reader->open($request->file('file')->getPathname());

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $i => $row) {
                if ($i === 1) {
                    continue;
                }
                $values = $row->toArray();
                if (count($values) < 2 || empty($values[0]) || empty($values[1])) {
                    continue;
                }

                $coop = Cooperative::updateOrCreate(
                    ['id' => $values[0]],
                    ['name' => $values[1]]
                );
                $regionId = isset($values[2]) ? Region::where('name', $values[2])->value('id') : null;
                $provinceId = isset($values[3]) ? Province::where('name', $values[3])->value('id') : null;
                $municipalityId = isset($values[4]) ? Municipality::where('name', $values[4])->value('id') : null;
                $barangayId = isset($values[5]) ? Barangay::where('name', $values[5])->value('id') : null;

                CoopDetail::updateOrCreate(
                    ['coop_id' => $coop->id],
                    [
                        'region_id' => $regionId ?? null,
                        'province_id' => $provinceId ?? null,
                        'municipality_id' => $municipalityId ?? null,
                        'barangay_id' => $barangayId ?? null,
                        'asset_size' => $values[6] ?? null,
                        'coop_type' => $values[7] ?? null,
                        'status_category' => $values[8] ?? null,
                        'bond_of_membership' => $values[9] ?? null,
                        'area_of_operation' => $values[10] ?? null,
                        'citizenship' => $values[11] ?? null,
                        'members_count' => $values[12] ?? null,
                        'total_asset' => $values[13] ?? null,
                        'net_surplus' => $values[14] ?? null,
                    ]
                );
            }
        }

        $reader->close();

        return redirect()->route('cooperatives.index')->with('success', 'File has been imported successfully.');
    }

    public function export(Request $request, string $type)
    {
        $type = strtolower($type);
        if (! in_array($type, ['csv', 'xlsx'])) {
            return redirect()
                ->route('cooperatives.index')
                ->with('error', 'The export file type is not supported.');
        }

        $fileName = 'cooperatives_'.now()->format('Ymd_His').'.'.$type;
        $filePath = storage_path("app/$fileName");

        if ($type === 'csv') {
            $options = new CsvWriterOptions;
            $options->FIELD_DELIMITER = '|';
            $writer = new CsvWriter($options);
        } else {
            $writer = new XlsxWriter;
        }
        $writer->openToFile($filePath);

        $writer->addRow(Row::fromValues([
            'Registration Number',
            'Cooperative Name',
            'Region',
            'Province',
            'Municipality',
            'Barangay',
            'Asset Size',
            'Coop Type',
            'Status Category',
            'Bond of Membership',
            'Area of Operation',
            'Citizenship',
            'Members Count',
            'Total Asset',
            'Net Surplus',
        ]));

        $coops = Cooperative::with('details.region', 'details.province', 'details.municipality', 'details.barangay')->get();

        foreach ($coops as $coop) {
            $d = $coop->details;
            $writer->addRow(Row::fromValues([
                $coop->id,
                $coop->name,
                $d->region->name ?? '',
                $d->province->name ?? '',
                $d->municipality->name ?? '',
                $d->barangay->name ?? '',
                $d->asset_size ?? '',
                $d->coop_type ?? '',
                $d->status_category ?? '',
                $d->bond_of_membership ?? '',
                $d->area_of_operation ?? '',
                $d->citizenship ?? '',
                $d->members_count ?? '',
                $d->total_asset ?? '',
                $d->net_surplus ?? '',
            ]));
        }

        $writer->close();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
