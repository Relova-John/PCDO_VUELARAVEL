<?php

namespace App\Http\Controllers;

use App\Models\AmortizationSchedules;
use App\Models\Cooperative;
use App\Models\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // Shows the whole Dashboard
    public function index()
    {
        // Count all registered cooperatives
        $totalCoops = Cooperative::count();

        // Next month range
        $startNextMonth = now()->startOfMonth()->addMonth();
        $endNextMonth = now()->endOfMonth()->addMonth();

        // Sum installments due next month
        $upcomingMonthlyDues = AmortizationSchedules::whereBetween('due_date', [$startNextMonth, $endNextMonth])
            ->sum('installment');

        // Cash Flow totals
        $totalReleases = AmortizationSchedules::sum('installment');
        $totalReceived = AmortizationSchedules::sum('amount_paid');

        $driver = DB::connection()->getDriverName();

        // Monthly and 5 Year program stats
        if ($driver === 'sqlite') {
            $monthlyProgramStats = DB::table('coop_programs')
                ->join('programs', 'programs.id', '=', 'coop_programs.program_id')
                ->select(
                    'programs.id as program_id',
                    'programs.name as program_name',
                    DB::raw("CAST(strftime('%m', start_date) AS INTEGER) as month"),
                    DB::raw('COUNT(DISTINCT coop_id) as coop_count')
                )
                ->whereBetween('start_date', [now()->startOfYear(), now()->endOfYear()])
                ->groupBy('programs.id', 'programs.name', 'month')
                ->get();

            $yearlyProgramStats = DB::table('coop_programs')
                ->join('programs', 'programs.id', '=', 'coop_programs.program_id')
                ->select(
                    'programs.id as program_id',
                    'programs.name as program_name',
                    DB::raw("CAST(strftime('%Y', start_date) AS INTEGER) as year"),
                    DB::raw('COUNT(DISTINCT coop_id) as coop_count')
                )
                ->whereBetween('start_date', [now()->subYears(5)->startOfYear(), now()->endOfYear()])
                ->groupBy('programs.id', 'programs.name', 'year')
                ->orderBy('year')
                ->get();
        } else {
            $monthlyProgramStats = DB::table('coop_programs')
                ->join('programs', 'programs.id', '=', 'coop_programs.program_id')
                ->select(
                    'programs.id as program_id',
                    'programs.name as program_name',
                    DB::raw('MONTH(start_date) as month'),
                    DB::raw('COUNT(DISTINCT coop_id) as coop_count')
                )
                ->whereYear('start_date', now()->year)
                ->groupBy('programs.id', 'programs.name', 'month')
                ->get();

            $yearlyProgramStats = DB::table('coop_programs')
                ->join('programs', 'programs.id', '=', 'coop_programs.program_id')
                ->select(
                    'programs.id as program_id',
                    'programs.name as program_name',
                    DB::raw('YEAR(start_date) as year'),
                    DB::raw('COUNT(DISTINCT coop_id) as coop_count')
                )
                ->whereYear('start_date', '>=', now()->subYears(5)->year)
                ->groupBy('programs.id', 'programs.name', 'year')
                ->orderBy('year')
                ->get();
        }

        // Unique program IDs
        $programIds = $monthlyProgramStats->pluck('program_id')
            ->merge($yearlyProgramStats->pluck('program_id'))
            ->unique()
            ->values();

        // Month labels Jan..Dec
        $monthlyCategories = collect(range(1, 12))->map(fn ($m) => Carbon::create()->month($m)->format('M'))->toArray();

        // Monthly data per program
        $monthlyData = $programIds->map(function ($pid) use ($monthlyProgramStats) {
            $name = optional($monthlyProgramStats->firstWhere('program_id', $pid))->program_name
                ?? DB::table('programs')->where('id', $pid)->value('name')
                ?? "Program {$pid}";

            $data = collect(range(1, 12))->map(fn ($m) => optional($monthlyProgramStats->firstWhere(fn ($r) => $r->program_id == $pid && $r->month == $m))->coop_count ?? 0
            )->toArray();

            return ['name' => $name, 'data' => $data];
        })->values()->toArray();

        // Year labels
        $years = $yearlyProgramStats->pluck('year')->unique()->sort()->values();
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        // Yearly data per program
        $yearlyData = $programIds->map(function ($pid) use ($yearlyProgramStats, $years) {
            $name = optional($yearlyProgramStats->firstWhere('program_id', $pid))->program_name
                ?? DB::table('programs')->where('id', $pid)->value('name')
                ?? "Program {$pid}";

            $data = $years->map(fn ($y) => optional($yearlyProgramStats->firstWhere(fn ($r) => $r->program_id == $pid && $r->year == $y))->coop_count ?? 0
            )->toArray();

            return ['name' => $name, 'data' => $data];
        })->values()->toArray();

        // Fetch recent pending notifications (last 5)
        $notifications = Notifications::with('coopProgram.cooperative')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get()
            ->map(function ($n) {
                // Get cooperative name through coopProgram relationship
                $coopName = optional($n->coopProgram->cooperative)->name
                            ?? optional($n->cooperative)->name
                            ?? 'Cooperative';

                return [
                    'id' => $n->id,
                    'subject' => ! empty($n->subject)
                        ? "{$coopName} - {$n->subject}"
                        : "Reminder for {$coopName}",
                    'body' => 'This is a pending payment notification for your cooperative.',
                    'type' => $n->type ?? 'info',
                    'created_at' => $n->created_at ?? now(),
                    'read' => $n->processed ?? false,
                ];
            });

        return Inertia::render('Dashboard', [
            'totalCoops' => $totalCoops,
            'upcomingMonthlyDues' => $upcomingMonthlyDues,
            'totalReleases' => $totalReleases,
            'totalReceived' => $totalReceived,
            'monthlyData' => $monthlyData,
            'monthlyCategories' => $monthlyCategories,
            'yearlyData' => $yearlyData,
            'yearlyCategories' => $years->toArray(),
            'notifications' => $notifications,
        ]);
    }
}
