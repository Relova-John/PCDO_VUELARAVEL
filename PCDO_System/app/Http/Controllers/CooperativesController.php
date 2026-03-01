<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use App\Models\CoopDetail;
use App\Models\Cooperative;
use App\Models\Programs;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use App\Mail\UserMail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
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
            ->withCount([
                'programs as ongoing_program_count' => function ($q) {
                    $q->where('program_status', 'ongoing');
                },
                'programs as delinquent_history_count' => function ($q) {
                    $q->whereHas('delinquents');
                },
                'programs as total_program_count',
            ])
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
                    'delinquent_history_count' => $coop->delinquent_history_count,
                    'total_program_count' => $coop->total_program_count,
                ];
            });

        return inertia('cooperatives/index', [
            'cooperatives' => $cooperatives,
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
            ],
        ]);
    }

    public function getDetails($id)
    {
        $coop = Cooperative::with('coopDetail')->findOrFail($id);

        return response()->json([
            'number' => optional($coop->coopDetail)->number,
            'email' => optional($coop->coopDetail)->email,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cooperatives = Cooperative::orderBy('name')->get(['id', 'name']);
        $regions = Region::orderBy('name')->get(['code', 'name']);
        $provinces = Province::orderBy('name')->get(['code', 'name', 'region_code']);
        $cities = City::orderBy('name')->get(['code', 'name', 'province_code', 'region_code']);
        $barangays = Barangay::orderBy('name')->get(['code', 'name', 'city_code']);

        return inertia('cooperatives/create', [
            'cooperatives' => $cooperatives,
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
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

            'region_code' => 'required|string',
            'province_code' => 'nullable|string',
            'city_code' => 'required|string',
            'barangay_code' => 'required|string',

            'asset_size' => ['required', Rule::in(['Micro', 'Small', 'Medium', 'Large'])],
            'coop_type' => ['required', Rule::in([
                'Credit',
                'Consumers',
                'Producers',
                'Marketing',
                'Service',
                'Multipurpose',
                'Advocacy',
                'Agrarian Reform',
                'Bank',
                'Diary',
                'Education',
                'Electric',
                'Financial',
                'Fishermen',
                'Health Services',
                'Housing',
                'Insurance',
                'Water Service',
                'Workers',
                'Others',
            ])],
            'status_category' => ['required', Rule::in(['Reporting', 'Non-Reporting', 'New'])],
            'bond_of_membership' => ['required', Rule::in([
                'Residential',
                'Insitutional',
                'Associational',
                'Occupational',
                'Unspecified',
            ])],
            'area_of_operation' => ['required', Rule::in(['Municipal', 'Provincial'])],
            'citizenship' => ['required', Rule::in(['Filipino', 'Foreign', 'Others'])],

            'members_count' => 'required|integer|min:1',
            'total_asset' => 'required|numeric|min:0',
            'net_surplus' => 'required|numeric',
            'email' => 'required|string|max:50',
            'number' => 'required|string|max:20',
        ]);

        if ($data['email'] && User::where('email', $data['email'])->exists()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => 'The email has already been taken by another user.']);
        }

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

        if ($data['email']) {
            $data['password'] = bin2hex(random_bytes(8));
            Mail::to($data['email'])->send(new UserMail($data['password']));
            $data['active'] = true;

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'active' => $data['active'] ?? true,
            ]);
            $user->assignRole('cooperative');
            $cooperative->user_id = $user->id;
            $cooperative->save();
        }

        $detailsData = [
            'coop_id' => $cooperative->id,
            'user_id' => $user->id ?? null,
            'region_code' => $data['region_code'],
            'province_code' => $data['province_code'],
            'city_code' => $data['city_code'],
            'barangay_code' => $data['barangay_code'],
            'asset_size' => $data['asset_size'],
            'coop_type' => $data['coop_type'],
            'status_category' => $data['status_category'],
            'bond_of_membership' => $data['bond_of_membership'],
            'area_of_operation' => $data['area_of_operation'],
            'citizenship' => $data['citizenship'],
            'members_count' => $data['members_count'],
            'total_asset' => $data['total_asset'],
            'net_surplus' => $data['net_surplus'],
            'email' => $data['email'],
            'number' => $data['number'],
        ];

        CoopDetail::create($detailsData);

        return redirect()
            ->route('cooperatives.show', $cooperative);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cooperative $cooperative)
    {
        $cooperative->load('details');

        // Get all programs to populate the dropdown
        $programs = Programs::select('id', 'name')->get();

        // Provide default empty details if none exist
        $details = $cooperative->details ? [
            'coop_type' => $cooperative->details->coop_type,
            'status_category' => $cooperative->details->status_category,
            'bond_of_membership' => $cooperative->details->bond_of_membership,
            'area_of_operation' => $cooperative->details->area_of_operation,
            'citizenship' => $cooperative->details->citizenship,
            'members_count' => $cooperative->details->members_count,
            'total_asset' => $cooperative->details->total_asset,
            'net_surplus' => $cooperative->details->net_surplus,
            'region' => $cooperative->details->region->name ?? '',
            'province' => $cooperative->details->province->name ?? '',
            'city' => $cooperative->details->city->name ?? '',
            'barangay' => $cooperative->details->barangay->name ?? '',
            'email' => $cooperative->details->email ?? '',
            'number' => $cooperative->details->number ?? '',
        ] : (object) [
            'coop_type' => '',
            'status_category' => '',
            'bond_of_membership' => '',
            'area_of_operation' => '',
            'citizenship' => '',
            'members_count' => 0,
            'total_asset' => 0,
            'net_surplus' => 0,
            'region' => '',
            'province' => '',
            'city' => '',
            'barangay' => '',
            'email' => '',
            'number' => '',
        ];

        $coopPrograms = $cooperative->programs()
            ->whereIn('program_status', ['Finished', 'Resolved'])
            ->where('exported', 1)
            ->where('archived', 1)
            ->with('program', 'delinquents')
            ->get();

        $groupedByYear = $coopPrograms->groupBy(function ($program) {
            return $program->updated_at->format('Y');
        });

        $minYear = $coopPrograms->min(fn($p) => $p->updated_at->year) ?? date('Y');
        $maxYear = date('Y');

        $history = collect(range($minYear, $maxYear))->map(function ($year) use ($groupedByYear) {
            $items = $groupedByYear->get($year, collect());

            return [
                'year' => $year,
                'programs' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'program_name' => $item->program->name ?? 'N/A',
                        'completed_at' => $item->updated_at->format('Y-m-d'),
                        'status' => $item->program_status,
                        'has_delinquent' => $item->delinquents->isNotEmpty(),
                    ];
                })->values(),
            ];
        })->sortDesc()->values();

        return inertia('cooperatives/show', [
            'cooperative' => $cooperative,
            'details' => $details,
            'programs' => $programs,
            'hasOngoingProgram' => $cooperative->programs()->where('program_status', 'ongoing')->exists(),
            'history' => $history,
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
            'cooperatives' => Cooperative::orderBy('name')->get(['id', 'name']),
            'cooperative' => $cooperative,
            'details' => $cooperative->details,
            'regions' => Region::orderBy('name')->get(['code', 'name']),
            'provinces' => Province::orderBy('name')->get(['code', 'name', 'region_code']),
            'cities' => City::orderBy('name')->get(['code', 'name', 'province_code', 'region_code']),
            'barangays' => Barangay::orderBy('name')->get(['code', 'name', 'city_code']),
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
            'id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cooperatives', 'id')->ignore($cooperative->id, 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cooperatives', 'name')->ignore($cooperative->id, 'id'),
            ],

            'region_code' => 'required|string',
            'province_code' => 'nullable|string',
            'city_code' => 'required|string',
            'barangay_code' => 'required|string',

            'asset_size' => ['required', Rule::in(['Micro', 'Small', 'Medium', 'Large'])],
            'coop_type' => ['required', Rule::in([
                'Credit',
                'Consumers',
                'Producers',
                'Marketing',
                'Service',
                'Multipurpose',
                'Advocacy',
                'Agrarian Reform',
                'Bank',
                'Diary',
                'Education',
                'Electric',
                'Financial',
                'Fishermen',
                'Health Services',
                'Housing',
                'Insurance',
                'Water Service',
                'Workers',
                'Others',
            ])],
            'status_category' => ['required', Rule::in(['Reporting', 'Non-Reporting', 'New'])],
            'bond_of_membership' => ['required', Rule::in([
                'Residential',
                'Insitutional',
                'Associational',
                'Occupational',
                'Unspecified',
            ])],
            'area_of_operation' => ['required', Rule::in(['Municipal', 'Provincial'])],
            'citizenship' => ['required', Rule::in(['Filipino', 'Foreign', 'Others'])],

            'members_count' => 'required|integer|min:1',
            'total_asset' => 'required|numeric|min:0',
            'net_surplus' => 'required|numeric',
            'email' => 'required|string|max:25',
            'number' => 'required|string|max:20',
        ]);

        $cooperative->update([
            'id' => $data['id'],
            'name' => $data['name'],
        ]);

        $cooperative->details()->updateOrCreate(
            ['coop_id' => $cooperative->id],
            [
                'region_code' => $data['region_code'],
                'province_code' => $data['province_code'],
                'city_code' => $data['city_code'],
                'barangay_code' => $data['barangay_code'],
                'asset_size' => $data['asset_size'],
                'coop_type' => $data['coop_type'],
                'status_category' => $data['status_category'],
                'bond_of_membership' => $data['bond_of_membership'],
                'area_of_operation' => $data['area_of_operation'],
                'citizenship' => $data['citizenship'],
                'members_count' => $data['members_count'],
                'total_asset' => $data['total_asset'],
                'net_surplus' => $data['net_surplus'],
                'email' => $data['email'],
                'number' => $data['number'],
            ]
        );

        if ($data['email'] && User::where('email', $data['email'])->where('id', '!=', $cooperative->user_id)->exists()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => 'The email has already been taken by another user.']);
        }

        if ($data['email'] && $cooperative->user_id) {
            $user = User::find($cooperative->user_id);
            if ($user) {
                $user->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);
            }
        } elseif ($data['email'] && ! $cooperative->user_id) {
            $data['password'] = bin2hex(random_bytes(8));
            Mail::to($data['email'])->send(new UserMail($data['password']));
            $data['active'] = true;

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'active' => $data['active'] ?? true,
            ]);
            $user->assignRole('cooperative');
            $cooperative->user_id = $user->id;
            $cooperative->save();
        } elseif (! $data['email'] && $cooperative->user_id) {
            $user = User::find($cooperative->user_id);
            if ($user) {
                $user->delete();
            }
            $cooperative->user_id = null;
            $cooperative->save();
        }

        return redirect()
            ->route('cooperatives.show', $cooperative->id)
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
                ->route('cooperatives.index');
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
                ->route('cooperatives.index');
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
                $regionCode = isset($values[2]) ? Region::where(['name' => $values[2]])->value('code') : null;
                $provinceCode = isset($values[3]) ? Province::where(['name' => $values[3]])->value('code') : null;
                $cityCode = isset($values[4]) ? City::where(['name' => $values[4]])->value('code') : null;
                $barangayCode = isset($values[5]) ? Barangay::where(['name' => $values[5]])->value('code') : null;

                CoopDetail::updateOrCreate(
                    ['coop_id' => $coop->id],
                    [
                        'region_code' => $regionCode ?? null,
                        'province_code' => $provinceCode ?? null,
                        'city_code' => $cityCode ?? null,
                        'barangay_code' => $barangayCode ?? null,
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
                ->route('cooperatives.index');
        }

        $fileName = 'cooperatives_' . now()->format('Ymd_His') . '.' . $type;
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
            'City',
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
            'Email',
            'Contact Number',
        ]));

        $coops = Cooperative::with('details.region', 'details.province', 'details.city', 'details.barangay')->get();

        foreach ($coops as $coop) {
            $d = $coop->details;
            $writer->addRow(Row::fromValues([
                $coop->id,
                $coop->name,
                $d->region->name ?? '',
                $d->province->name ?? '',
                $d->city->name ?? '',
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
                $d->email ?? '',
                $d->number ?? '',
            ]));
        }

        $writer->close();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
