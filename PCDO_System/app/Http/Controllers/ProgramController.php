<?php

namespace App\Http\Controllers;

use App\Mail\CoopProgramEnrolled;
use App\Models\AmortizationSchedules;
use App\Models\Cooperative;
use App\Models\CoopProgram;
use App\Models\Notifications;
use App\Models\Programs;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Programs::withCount('coopProgram')->get();
        $cities = City::orderBy('name')->get(['code', 'name']);
        $cities = collect([
            [
                'code' => 'all',
                'name' => 'All Municipality',
                'province_code' => null,
                'region_code' => null,
            ]
        ])->merge($cities);

        return inertia('programs/index', [
            'programs' => $programs->map(fn($program) => [
                'id' => $program->id,
                'name' => $program->name,
                'details' => $program->details,
                'active_cooperatives' => $program->coopProgram()->where('program_status', 'Ongoing')->count(),
                'cooperatives_count' => $program->coop_program_count,
                'archive' => $program->archive,

            ]),
            'cities' => $cities,
        ]);
    }

    public function show($id)
    {
        $program = Programs::findOrFail($id);

        $cooperatives = CoopProgram::with('cooperative')
            ->where('program_id', $id)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($cp) => [
                'id' => $cp->cooperative->id,
                'name' => $cp->cooperative->name,
                'start_date' => $cp->start_date,
                'program_status' => $cp->program_status,
                'has_checklist' => $cp->checklist()->exists(),
                'has_amortization' => $cp->amortizationSchedules()->exists(),
                'coopProgramId' => $cp->id,
            ]);

        return inertia('programs/show', [
            'program' => $program,
            'cooperatives' => $cooperatives,
        ]);
    }

    public function createCooperative(Programs $program): Response
    {
        $cooperatives = Cooperative::whereDoesntHave('programs', function ($q) {
            $q->where('program_status', 'Ongoing');
        })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('programs/createCoop', [
            'program' => $program,
            'cooperatives' => $cooperatives,
        ]);
    }

    public function storeCooperative(Request $request, Programs $program)
    {
        $data = $request->validate([
            'cooperative_id' => 'required|exists:cooperatives,id',
            'project' => 'required|string|max:255',
        ]);

        $cooperative = Cooperative::findOrFail($data['cooperative_id']);

        $ongoingPrograms = CoopProgram::where('coop_id', $cooperative->id)
            ->where('program_status', 'Ongoing')
            ->with('program')
            ->get();

        foreach ($ongoingPrograms as $ongoing) {
            if ($ongoing->program_id === $program->id) {
                return back()->withErrors(['program_id' => 'This program is already ongoing.']);
            }
            if ($program->name === 'LICAP' && $ongoing->program->name === 'LICAP') {
                return back()->withErrors(['program_id' => 'LICAP program already ongoing.']);
            }
            if ($program->name !== 'LICAP' && $ongoing->program->name !== 'LICAP') {
                return back()->withErrors(['program_id' => 'Cannot enroll in another non-LICAP program while one is ongoing.']);
            }
        }

        $coopProgram = CoopProgram::create([
            'coop_id' => $cooperative->id,
            'program_id' => $program->id,
            'project' => $data['project'],
            'end_date' => now()->addMonths($program->term_months),
            'program_status' => 'Ongoing',
            'start_date' => now(),
            'loan_amount' => null,
            'with_grace' => null,
        ]);

        Notifications::create([
            'schedule_id' => null,
            'coop_id' => $cooperative->id,
            'type' => 'enrolled',
            'subject' => 'Cooperative Enrolled in Program',
            'body' => "The cooperative '{$cooperative->name}' has been enrolled in the '{$program->name}' program on " . now()->setTimezone('Asia/Manila')->format('F j, Y') . '.',
            'processed' => 1,
        ]);

        if ($cooperative->coopDetail && $cooperative->coopDetail->email) {
            Mail::to($cooperative->coopDetail->email)
                ->send(new CoopProgramEnrolled($cooperative, $program));
        }

        return redirect()->route(
            'programs.cooperatives.checklist.show',
            [
                'coopProgramId' => $coopProgram->id,
            ]
        )->with('success', 'Program enrolled successfully. Notification logged.');
    }

    public function finalizeLoan(Request $request, $cp)
    {
        $request->validate([
            'loan_amount' => 'required|numeric|min:1',
            'with_grace' => 'required|numeric',
            'start_date' => 'required|date',
        ]);

        $coopProgram = CoopProgram::findOrFail($cp);

        if (!$coopProgram) {
            return back()->withErrors(['error' => 'The Cooperative  exist for this cooperative.']);
        }

        if ($request->loan_amount < $coopProgram->program->min_amount || $request->loan_amount > $coopProgram->program->max_amount) {
            return back()->withErrors([
                'loan_amount' => "Loan amount must be between ₱{$coopProgram->program->min_amount} and ₱{$coopProgram->program->max_amount}",
            ]);
        }

        $startDate = Carbon::parse($request->start_date)->addMonths($request->with_grace);

        $coopProgram->update([
            'loan_amount' => $request->loan_amount,
            'with_grace' => $request->with_grace,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($coopProgram->program->term_months),
        ]);

        if (! $coopProgram->amortizationSchedules()->exists()) {
            $monthsToPay = $coopProgram->program->term_months - $coopProgram->with_grace;
            if ($monthsToPay <= 0) {
                return back()->withErrors(['loan_amount' => 'Invalid term or grace period.']);
            }

            $amountPerMonth = round($coopProgram->loan_amount / $monthsToPay, 2);

            for ($i = 1; $i <= $monthsToPay; $i++) {
                $amountDue = ($i === $monthsToPay)
                    ? $coopProgram->loan_amount - ($amountPerMonth * ($monthsToPay - 1))
                    : $amountPerMonth;

                AmortizationSchedules::create([
                    'coop_program_id' => $coopProgram->id,
                    'due_date' => $startDate->copy()->addMonthsNoOverflow($i - 1),
                    'installment' => $amountDue,
                    'current_balance' => $amountDue,
                    'status' => 'Unpaid',
                ]);
            }

            Notifications::create([
                'schedule_id' => null,
                'coop_id' => $coopProgram->coop_id,
                'type' => 'has_schedule',
                'subject' => 'Amortization Schedule Created',
                'body' => "The cooperative '{$coopProgram->cooperative->name}' has been issued an amortization schedule under the '{$coopProgram->program->name}' program. First due date: " . $startDate->format('F d, Y') . '.',
                'processed' => 1,
            ]);

            $coopDetail = $coopProgram->cooperative->coopDetail;
            if ($coopDetail && $coopDetail->email) {
                $subject = 'Amortization Schedule Created';
                $body = "Dear {$coopProgram->cooperative->name},\n\nYour amortization schedule has been successfully generated under the program '{$coopProgram->program->name}'.\nYour first payment of ₱{$amountPerMonth} is due on " . $startDate->format('F d, Y') . ".\n\nThank you.";

                Mail::raw($body, function ($message) use ($coopDetail, $subject) {
                    $message->to($coopDetail->email)
                        ->subject($subject);
                });
            }
        }

        return redirect()
            ->route('amortizations.show', $coopProgram->id)
            ->with('success', 'Loan finalized and amortization schedule generated successfully!');
    }

    public function monthlyReport(Request $request)
    {
        $selectedProgram = $request->program ?? 'all';
        $selectedMunicipality = $request->municipality ?? 'all';

        $monthStart = $request->filled('month')
            ? Carbon::parse($request->month)->startOfMonth()
            : ($request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : now()->startOfMonth());

        $monthEnd = $request->filled('month')
            ? Carbon::parse($request->month)->endOfMonth()
            : ($request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : now()->endOfMonth());

        $dateLabel = $monthStart->isSameDay($monthEnd)
            ? $monthStart->format('F d, Y')
            : ($request->filled('month')
                ? $monthStart->format('F Y')
                : $monthStart->format('F d, Y') . ' - ' . $monthEnd->format('F d, Y'));

        $program = $selectedProgram !== 'all'
            ? Programs::select('id', 'name')->find($selectedProgram)
            : null;

        $selectedProgramName = $program->name ?? 'All Programs';
        $selectedProgramFileName = str_replace(' ', '_', $selectedProgramName);

        $municipality = $selectedMunicipality !== 'all'
            ? City::select('code', 'name')->find($selectedMunicipality)
            : null;

        $selectedMunicipalityName = $municipality->name ?? 'All Municipality';
        $selectedMunicipalityFileName = str_replace(' ', '_', $selectedMunicipalityName);

        $registeredCoops = CoopProgram::with(['program:id,name', 'cooperative:id,name'])
            ->when($selectedProgram !== 'all', fn($q) => $q->where('program_id', $selectedProgram))
            ->when($selectedMunicipality !== 'all', function ($q) use ($selectedMunicipality) {
                $q->whereHas('cooperative', function ($coop) use ($selectedMunicipality) {
                    $coop->whereHas('details', function ($details) use ($selectedMunicipality) {
                        $details->where('city_code', $selectedMunicipality);
                    });
                });
            })
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->get()
            ->map(fn($cp) => (object)[
                'cooperative_name' => $cp->cooperative->name,
                'registered_at' => $cp->start_date,
                'program_name' => $cp->program->name,
            ]);

        $finishedCoops = CoopProgram::with(['program:id,name', 'cooperative:id,name'])
            ->when($selectedProgram !== 'all', fn($q) => $q->where('program_id', $selectedProgram))
            ->when($selectedMunicipality !== 'all', function ($q) use ($selectedMunicipality) {
                $q->whereHas('cooperative', function ($coop) use ($selectedMunicipality) {
                    $coop->whereHas('details', function ($details) use ($selectedMunicipality) {
                        $details->where('city_code', $selectedMunicipality);
                    });
                });
            })
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->where(function ($q2) use ($monthStart, $monthEnd) {
                    $q2->where('program_status', 'Completed')
                        ->whereBetween('end_date', [$monthStart, $monthEnd]);
                })->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                    $q2->where('program_status', 'Resolved')
                        ->whereBetween('updated_at', [$monthStart, $monthEnd]);
                });
            })
            ->get()
            ->map(fn($cp) => (object)[
                'cooperative_name' => $cp->cooperative->name,
                'finished_at' => $cp->program_status === 'Completed'
                    ? $cp->end_date
                    : $cp->updated_at,
                'program_name' => $cp->program->name,
                'status' => $cp->program_status,
            ]);

        $programs = Programs::with([
            'coopProgram' => function ($q) use ($selectedProgram, $selectedMunicipality, $monthStart, $monthEnd) {
                if ($selectedProgram !== 'all') {
                    $q->where('program_id', $selectedProgram);
                }

                if ($selectedMunicipality !== 'all') {
                    $q->whereHas('cooperative', function ($coop) use ($selectedMunicipality) {
                        $coop->whereHas('details', function ($details) use ($selectedMunicipality) {
                            $details->where('city_code', $selectedMunicipality);
                        });
                    });
                }

                $q->where('start_date', '<=', $monthEnd);
                $q->where(function ($q2) use ($monthEnd) {
                    $q2->where('program_status', 'Ongoing')
                        ->orWhere('end_date', '>=', $monthEnd)
                        ->orWhere('updated_at', '>=', $monthEnd);
                });
                $q->with([
                    'cooperative:id,name',
                    'amortizationSchedules' => function ($q3) use ($monthEnd) {
                        $q3->where('due_date', '<=', $monthEnd)
                            ->select('coop_program_id', 'amount_paid', 'penalty_amount', 'date_paid', 'due_date', 'status');
                    }
                ]);
            }
        ])
            ->when($selectedProgram !== 'all', fn($q) => $q->where('id', $selectedProgram))
            ->get()
            ->map(function ($program) use ($monthStart, $monthEnd) {

                $hasAmortization = [];
                $checklistOnly = [];

                foreach ($program->coopProgram as $cp) {

                    $paid = $cp->amortizationSchedules
                        ->whereNotNull('date_paid')
                        ->where('date_paid', '<=', $monthEnd);

                    if ($cp->amortizationSchedules->isNotEmpty()) {
                        $totalPaid = $paid->sum('amount_paid');
                        $totalPenalty = $paid->sum('penalty_amount');
                        $totalLoan = $cp->loan_amount ?? 0;
                        $remainingBalance = $totalLoan - $totalPaid;
                        $dueSchedules = $cp->amortizationSchedules
                            ->where('due_date', '<=', $monthEnd);

                        $overdueCount = $dueSchedules
                            ->where('status', 'Overdue')
                            ->count();

                        // $paidCount = $dueSchedules
                        //     ->where('status', 'Paid')
                        //     ->count();

                        // $partialCount = $dueSchedules
                        //     ->where('status', 'Partial Paid')
                        //     ->count();

                        // $pendingCount = $dueSchedules
                        //     ->where('status', 'Pending')
                        //     ->count();

                        $currentInstallment = $cp->amortizationSchedules
                            ->where('due_date', '<=', $monthEnd)
                            ->sortByDesc('due_date')
                            ->first();

                        $currentStatus = $currentInstallment?->status ?? 'Ongoing';

                        $lastPaid = $cp->amortizationSchedules
                            ->where('date_paid', '<=', $monthEnd)
                            ->whereNotNull('date_paid')
                            ->sortByDesc('date_paid')
                            ->first();

                        $hasAmortization[] = [
                            'cooperative_name' => $cp->cooperative->name,
                            'payment_status' => $currentStatus,
                            'overdue_status' => $overdueCount,
                            'loan_amount' => $totalLoan,
                            'amount_paid' => $totalPaid,
                            'remaining_balance' => $remainingBalance,
                            'last_paid' => optional($lastPaid?->date_paid)->format('F d, Y'),
                            'penalty' => $totalPenalty,
                        ];
                    } elseif (!in_array($cp->program_status, ['Resolved', 'Completed'])) {

                        $checklistOnly[] = [
                            'cooperative_name' => $cp->cooperative->name,
                            'program_status' => $cp->program_status,
                        ];
                    }
                }

                return [
                    'program_name' => $program->name,
                    'has_amortization' => $hasAmortization,
                    'checklist_only' => $checklistOnly,
                ];
            });

        $pdf = Pdf::loadView('monthly', [
            'selectedProgram' => $selectedProgramName,
            'date' => $dateLabel,
            'registeredCoops' => $registeredCoops,
            'finishedCoops' => $finishedCoops,
            'programs' => $programs,
        ])->setPaper('a4', 'portrait');

        $fileName = $selectedProgramFileName . '_' . $selectedMunicipalityFileName . '_' .
            str_replace([',', ' '], ['', '_'], $dateLabel) . '_Report' . '.pdf';

        return $request->has('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }
}
