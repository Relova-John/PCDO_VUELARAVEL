<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CoopController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $coop = $user->cooperatives()->with([
            'programs.amortizationSchedules',
        ])->first();

        if (! $coop) {
            abort(403, 'Unauthorized.');
        }

        $programs = $coop->programs;

        // Total Active Programs
        $activePrograms = $programs
            ->whereIn('program_status', ['Ongoing', 'Active'])
            ->load('program')
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->program->name ?? 'N/A',
                ];
            });
        // Total Loan Amount
        $totalLoanAmount = $programs->sum('loan_amount');

        // Total Paid (from amortization schedules)
        $totalPaid = 0;

        foreach ($programs as $program) {
            $totalPaid += $program->amortizationSchedules
                ->where('status', 'Paid')
                ->sum('installment');
        }

        // Remaining Balance
        $totalBalance = $totalLoanAmount - $totalPaid;

        // Monthly Chart Data (Current Year)
        $months = collect(range(1, 12))->map(function ($month) use ($programs) {
            $total = 0;

            foreach ($programs as $program) {
                $total += $program->amortizationSchedules
                    ->where('status', 'Paid')
                    ->filter(function ($schedule) use ($month) {
                        return Carbon::parse($schedule->paid_at)->month === $month;
                    })
                    ->sum('installment');
            }

            return $total;
        });

        $monthlyData = $months->values();

        $monthlyCategories = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];

        $members = $coop->members; // make sure relationship exists

        $memberCounts = [
            'Chairman' => $members->where('position', 'Chairman')->count(),
            'Treasurer' => $members->where('position', 'Treasurer')->count(),
            'Manager' => $members->where('position', 'Manager')->count(),
            'Member' => $members->where('position', 'Member')->count(),
        ];

        $totalMembers = $members->count();

        $checklists = $coop->programs()
            ->with('checklist')
            ->get()
            ->flatMap(function ($program) {
                return $program->checklist;
            });

        $totalChecklist = $checklists->count();
        $completedChecklist = $checklists->where('status', 'Completed')->count();

        return Inertia::render('coop/Dashboard', [
            'activePrograms' => $activePrograms,
            'totalLoanAmount' => $totalLoanAmount,
            'totalPaid' => $totalPaid,
            'totalBalance' => $totalBalance,
            'monthlyData' => $monthlyData,
            'monthlyCategories' => $monthlyCategories,
            'memberCounts' => $memberCounts,
            'totalMembers' => $totalMembers,
            'totalChecklist' => $totalChecklist,
            'completedChecklist' => $completedChecklist,
        ]);
    }

    public function details()
    {
        $cooperative = Cooperative::where('user_id', auth()->id())->first();

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

    public function checklist()
    {
        $cooperative = Cooperative::where('user_id', auth()->id())
            ->with([
                'programs.program.checklists',
                'programs.checklist',
                'programs.amortizationSchedules',
            ])
            ->firstOrFail();

        return Inertia::render('coop/checklist/index', [
            'coop' => $cooperative,
        ]);
    }

    public function schedules()
    {
        $user = auth()->user();

        $coop = $user->cooperatives()->first();

        if (! $coop) {
            abort(403, 'Unauthorized.');
        }

        $coopProgram = $coop->programs()
            ->with(['program', 'amortizationSchedules'])
            ->latest()
            ->first();

        if (! $coopProgram) {
            return Inertia::render('coop/schedule/index', [
                'coopProgram' => null,
            ]);
        }

        $schedules = $coopProgram->amortizationSchedules
            ->sortBy('due_date')
            ->values();

        return Inertia::render('coop/schedule/index', [
            'coopProgram' => [
                'id' => $coopProgram->id,
                'program_name' => $coopProgram->program->name ?? 'N/A',
                'loan_amount' => $coopProgram->loan_amount,
                'grace_period' => $coopProgram->grace_period,
                'term_months' => $coopProgram->term_months,
                'schedules' => $schedules->map(fn ($schedule) => [
                    'id' => $schedule->id,
                    'due_date' => $schedule->due_date,
                    'installment' => $schedule->installment,
                    'penalty_amount' => $schedule->penalty_amount,
                    'status' => $schedule->status,
                    'paid_at' => $schedule->paid_at,
                ]),
            ],
        ]);
    }
}
