<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use Inertia\Inertia;

class CoopController extends Controller
{
    public function index()
    {
        $cooperative = auth()->user()
            ->cooperatives()
            ->with([
                'details',
                'programs.checklist',
                'programs.amortizationSchedules',
                'programs.olds',
                'programs.program',
                'programs.program.checklists',
            ])
            ->get();

        return Inertia::render('coop/Dashboard', [
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'link' => route('coop.dashboard')],
            ],
            'cooperative' => $cooperative,
        ]);
    }

    public function details(Cooperative $cooperative)
    {
        $cooperative->load('details'); // load the relationship

        $details = (object) [
            'coop_type' => $cooperative->details->coop_type ?? '',
            'status_category' => $cooperative->details->status_category ?? '',
            'bond_of_membership' => $cooperative->details->bond_of_membership ?? '',
            'area_of_operation' => $cooperative->details->area_of_operation ?? '',
            'citizenship' => $cooperative->details->citizenship ?? '',
            'members_count' => $cooperative->details->members_count ?? 0,
            'total_asset' => $cooperative->details->total_asset ?? 0,
            'net_surplus' => $cooperative->details->net_surplus ?? 0,
            'region' => $cooperative->details->region->name ?? '',
            'province' => $cooperative->details->province->name ?? '',
            'city' => $cooperative->details->city->name ?? '',
            'barangay' => $cooperative->details->barangay->name ?? '',
            'email' => $cooperative->details->email ?? '',
            'number' => $cooperative->details->number ?? '',
        ];

        // Prepare history
        $coopPrograms = $cooperative->programs()
            ->whereIn('program_status', ['Finished', 'Resolved'])
            ->where('exported', 1)
            ->where('archived', 1)
            ->with('program', 'delinquents')
            ->get();

        $groupedByYear = $coopPrograms->groupBy(fn ($program) => $program->updated_at->format('Y'));
        $minYear = $coopPrograms->min(fn ($p) => $p->updated_at->year) ?? date('Y');
        $maxYear = date('Y');

        $history = collect(range($minYear, $maxYear))->map(fn ($year) => [
            'year' => $year,
            'programs' => $groupedByYear->get($year, collect())->map(fn ($item) => [
                'id' => $item->id,
                'program_name' => $item->program->name ?? 'N/A',
                'completed_at' => $item->updated_at->format('Y-m-d'),
                'status' => $item->program_status,
                'has_delinquent' => $item->delinquents->isNotEmpty(),
            ])->values(),
            'open' => true,
        ])->sortDesc()->values();

        return Inertia::render('coop/details/index', [
            'cooperative' => $cooperative,
            'details' => $details,
            'history' => $history,
        ]);
    }
}
