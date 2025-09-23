<?php

namespace App\Http\Controllers;
use App\Models\Cooperative;
use App\Models\Municipality;
use App\Models\CoopDetail;
use App\Models\Province;
use App\Models\Region;
use App\Models\Barangay;
use Illuminate\Http\Request;

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
        $cooperatives = Cooperative::orderBy('name')->get(['id', 'name',]);
        $regions = Region::orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name','region_id']);
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
            'name' => $data['name']
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
        return inertia('cooperatives.show', [
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
        return inertia('cooperatives.edit', [
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
            'name' => 'required|string|max:255|unique:cooperatives,name,' . $cooperative->id,
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
        $cooperative->delete();

        return redirect()
            ->route('cooperatives.index')
            ->with('success', 'Cooperative deleted successfully!');
    }

    public function import($cooperatives)
    {
        $csvData = [];
        $csvData[] = ['field' => 'Registration Number', 'value' => $coop->id ?? 'Unregistered'];
        $csvData[] = ['field' => 'Cooperative Name', 'value' => $coop->name ?? 'Unknown Cooperative'];
        $csvData[] = ['field' => 'Region', 'value' => $coop->region ?? 'Unknown Region'];
        $csvData[] = ['field' => 'Province', 'value' => $coop->province ?? 'Unknown Province'];
        $csvData[] = ['field' => 'Municipality', 'value' => $coop->municipality ?? 'Unregistered Municipality'];
        $csvData[] = ['field' => 'Barangay', 'value' => $coop->barangay ?? 'Unregistered Barangay'];
        $csvData[] = ['field' => 'Asset Size', 'value' => $coop->asset_size ?? 'Unknown Asset Size'];
        $csvData[] = ['field' => 'Coop Type', 'value' => $coop->coop_type ?? 'Unknown Cooperative Type'];
        $csvData[] = ['field' => 'Status Category', 'value' => $coop->status_category ?? 'Unknown Status / Category'];
        $csvData[] = ['field' => 'Bond of Membership', 'value' => $coop->bond_of_membership ?? 'Unknown Bond of Membership'];
        $csvData[] = ['field' => 'Area of Operation', 'value' => $coop->area_of_operation ?? 'Unknown Area of Operation'];
        $csvData[] = ['field' => 'Citizenship', 'value' => $coop->citizenship ?? 'Unregistered Citizenship'];
        $csvData[] = ['field' => 'Members Count', 'value' => $coop->members_count ?? 'Unregistered Members Count'];
        $csvData[] = ['field' => 'Total Asset', 'value' => $coop->total_asset ?? 'Unknown Total Asset'];
        $csvData[] = ['field' => 'Net_Surplus', 'value' => $coop->net_surplus ?? 'Unknown Net Surplus'];
    }
    public function export()
    {
        return inertia('cooperatives.export');
    }
}
