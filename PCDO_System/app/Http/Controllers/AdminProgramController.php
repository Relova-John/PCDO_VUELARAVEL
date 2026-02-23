<?php

namespace App\Http\Controllers;

use App\Mail\CoopProgramEnrolled;
use App\Models\AmortizationSchedules;
use App\Models\Checklists;
use App\Models\Cooperative;
use App\Models\CoopProgram;
use App\Models\CoopProgramChecklist;
use App\Models\FinishedCoopProgramChecklist;
use App\Models\Notifications;
use App\Models\Programs;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AdminProgramController extends Controller
{
    // Display a listing of the programs with cooperative count.

    public function index()
    {
        $programs = Programs::withCount('coopProgram')->get();
        $activePrograms = CoopProgram::where('program_status', 'Ongoing')->pluck('program_id')->toArray();

        return inertia('admin/programs/index', [
            'breadcrumbs' => [
                ['title' => 'Programs'],
            ],
            'programs' => $programs->map(fn ($program) => [
                'id' => $program->id,
                'name' => $program->name,
                'details' => $program->details,
                'active_coop_count' => $activePrograms[$program->id] ?? 0,
                'cooperatives_count' => $program->coop_program_count,
                'archive'=> $program->archive
            ]),
        ]);
    }

    // Show one program and its cooperatives.

    public function show($id)
    {
        $program = Programs::findOrFail($id);

        $cooperatives = CoopProgram::with('cooperative')
            ->where('program_id', $id)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn ($cp) => [
                'id' => $cp->cooperative->id,
                'name' => $cp->cooperative->name,
                'start_date' => $cp->start_date,
                'program_status' => $cp->program_status,
                'has_checklist' => $cp->checklist()->exists(),
                'has_amortization' => $cp->amortizationSchedules()->exists(),
                'coopProgramId' => $cp->id,
            ]);

        return inertia('admin/programs/show', [
            'breadcrumbs' => [
                ['title' => 'Programs', 'href' => route('admin.programs.index')],
                ['title' => $program->name],
            ],
            'program' => $program,
            'cooperatives' => $cooperatives,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/programs/create', [
            'breadcrumbs' => [
                ['title' => 'Programs', 'href' => route('admin.programs.index')],
                ['title' => 'Create Program'],
            ],
            'checklist' => Checklists::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function edit(Programs $program): Response
    {
        return Inertia::render('admin/programs/edit', [
            'breadcrumbs' => [
                ['title' => 'Programs', 'href' => route('admin.programs.index')],
                ['title' => $program->name, 'href' => route('admin.programs.show', $program)],
                ['title' => 'Edit'],
            ],
            'program' => $program,
            'checklist' => Checklists::orderBy('id')->get(['id', 'name']),
            'programChecklists' => $program->checklists()->pluck('checklist_id')->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'term_months' => 'required|integer|min:1',
            'grace_period' => 'required|integer|min:0',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0|gte:min_amount',
            'penalty' => 'required|integer|min:0',
        ]);

        $program = Programs::create($data);
        $program->checklists()->sync($request->input('selected_checklists', []));

        return redirect()->route('admin.programs.index')->with('success', 'Program created successfully!');
    }

    public function update(Request $request, Programs $program)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'term_months' => 'required|integer|min:1',
            'grace_period' => 'required|integer|min:0',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0|gte:min_amount',
            'penalty' => 'required|integer|min:0',
        ]);

        $program->update($data);
        $program->checklists()->sync($request->input('selected_checklists', []));

        return redirect()->route('admin.programs.index')->with('success', 'Program updated successfully!');
    }

    public function createCooperative(Programs $program): Response
    {
        $cooperatives = Cooperative::whereDoesntHave('programs', function ($q) {
            $q->where('program_status', 'Ongoing');
        })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/programs/createCoop', [
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

        // Same validation logic you had before...
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

        //  Log notification of enrollment
        Notifications::create([
            'schedule_id' => null,
            'coop_id' => $cooperative->id,
            'type' => 'enrolled',
            'subject' => 'Cooperative Enrolled in Program',
            'body' => "The cooperative '{$cooperative->name}' has been enrolled in the '{$program->name}' program on ".now()->setTimezone('Asia/Manila')->format('F j, Y').'.',
            'processed' => 1,
        ]);

        //  Send email notification if coop has an email
        if ($cooperative->coopDetail && $cooperative->coopDetail->email) {
            Mail::to($cooperative->coopDetail->email)
                ->send(new CoopProgramEnrolled($cooperative, $program));
        }

        //  Redirect to checklist show page
        return redirect()->route(
            'admin.programs.cooperatives.checklist.show',
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

        $coopProgram = CoopProgram::where('id', $cp)
             ->orderby('id', 'desc')
             ->first();

        dd($coopProgram);

        if (!$coopProgram) {
            return back()->withErrors(['loan_amount' => 'Program does not exist for this cooperative.']);
        }

        if ($request->loan_amount < $coopProgram->program->min_amount || $request->loan_amount > $coopProgram->program->max_amount) {
            return back()->withErrors([
                'loan_amount' => "Loan amount must be between ₱{$coopProgram->program->min_amount} and ₱{$coopProgram->program->max_amount}",
            ]);
        }

        $startDate = Carbon::parse($request->start_date)->addMonths($request->with_grace);

        //  Update coop program
        $coopProgram->update([
            'loan_amount' => $request->loan_amount,
            'with_grace' => $request->with_grace,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($coopProgram->program->term_months),
        ]);

        //  Auto-generate amortization schedule
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

            //  Log Notification
            Notifications::create([
                'schedule_id' => null,
                'coop_id' => $coopProgram->coop_id,
                'type' => 'has_schedule',
                'subject' => 'Amortization Schedule Created',
                'body' => "The cooperative '{$coopProgram->cooperative->name}' has been issued an amortization schedule under the '{$coopProgram->program->name}' program. First due date: ".$startDate->format('F d, Y').'.',
                'processed' => 1,
            ]);

            //  Optional: Send Email
            $coopDetail = $coopProgram->cooperative->coopDetail;
            if ($coopDetail && $coopDetail->email) {
                $subject = 'Amortization Schedule Created';
                $body = "Dear {$coopProgram->cooperative->name},\n\nYour amortization schedule has been successfully generated under the program '{$coopProgram->program->name}'.\nYour first payment of ₱{$amountPerMonth} is due on ".$startDate->format('F d, Y').".\n\nThank you.";

                Mail::raw($body, function ($message) use ($coopDetail, $subject) {
                    $message->to($coopDetail->email)
                        ->subject($subject);
                });
            }
        }

        //  Redirect to the loan tracker page after everything
        return redirect()
            ->route('amortizations.show', $coopProgram->id)
            ->with('success', 'Loan finalized and amortization schedule generated successfully!');
    }

    public function monthlyReport(Request $request)
    {
        $selectedMonth = $request->input('month')
            ? Carbon::parse($request->input('month'))
            : Carbon::now();

        $selectedProgramId = $request->input('program_id');
        $selectedProgramName = 'All Programs';
        $selectedProgramFileName = 'All_Programs';

        if ($selectedProgramId && $selectedProgramId !== 'all') {
            $program = Programs::find($selectedProgramId);
            if ($program) {
                $selectedProgramName = $program->name;
                $selectedProgramFileName = str_replace(' ', '_', $program->name);
            } else {
                $selectedProgramName = 'Unknown Program';
                $selectedProgramFileName = 'Unknown_Program';
            }
        }

        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        // 1️⃣ Registered Cooperatives
        $registeredCoopsQuery = CoopProgram::with('program', 'cooperative')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        if ($selectedProgramId && $selectedProgramId !== 'all') {
            $registeredCoopsQuery->where('program_id', $selectedProgramId);
        }

        $registeredCoops = $registeredCoopsQuery->get()->map(fn ($cp) => (object) [
            'cooperative_name' => $cp->cooperative->name,
            'registered_at' => $cp->created_at,
            'program_name' => $cp->program->name,
        ]);

        // 2️⃣ Completed Programs
        $finishedCoopsQuery = CoopProgram::with('program', 'cooperative')
            ->whereIn('program_status', ['Resolved', 'Completed'])
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->where('program_status', 'Completed')
                        ->whereBetween('end_date', [$monthStart, $monthEnd]);
                })
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->where('program_status', 'Resolved')
                            ->whereBetween('updated_at', [$monthStart, $monthEnd]);
                    });
            });

        if ($selectedProgramId && $selectedProgramId !== 'all') {
            $finishedCoopsQuery->where('program_id', $selectedProgramId);
        }

        $finishedCoops = $finishedCoopsQuery->get()->map(fn ($cp) => (object) [
            'cooperative_name' => $cp->cooperative->name,
            'finished_at' => $cp->program_status === 'Completed' ? $cp->end_date : $cp->updated_at,
            'program_name' => $cp->program->name,
            'status' => $cp->program_status,
        ]);

        // 3️⃣ Programs Summary
        $programsQuery = Programs::with(['coopProgram.cooperative', 'coopProgram.amortizationSchedules']);

        if ($selectedProgramId && $selectedProgramId !== 'all') {
            $programsQuery->where('id', $selectedProgramId);
        }

        $programs = $programsQuery->get()->map(function ($program) use ($monthStart, $monthEnd) {
            $hasAmortization = [];
            $checklistOnly = [];
            $coopPrograms = $program->coopProgram ? collect($program->coopProgram) : collect();

            foreach ($coopPrograms as $cp) {
                $amortizations = $cp->amortizationSchedules ?? collect();

                if ($amortizations->count() > 0) {
                    $paidAmortizations = $amortizations->filter(fn ($a) => $a->date_paid <= $monthEnd);
                    $totalPaid = $paidAmortizations->sum('amount_paid');
                    $totalLoan = $cp->loan_amount;
                    $totalPenalty = $paidAmortizations->sum('penalty_amount');
                    $remaining = $totalLoan - $totalPaid;
                    $lastPaid = $paidAmortizations->sortByDesc('date_paid')->first();
                    $lastPaidDate = $lastPaid?->date_paid?->format('F d, Y');

                    $thisMonthPayments = $amortizations->filter(fn ($a) => $a->date_paid >= $monthStart && $a->date_paid <= $monthEnd);

                    if ($thisMonthPayments->sum('amount_paid') == 0) {
                        $monthStatus = 'Overdue';
                    } elseif ($thisMonthPayments->sum('amount_paid') < ($totalLoan + $totalPenalty)) {
                        $monthStatus = 'Partial Paid';
                    } else {
                        $monthStatus = 'Fully Paid';
                    }

                    $hasAmortization[] = [
                        'cooperative_name' => $cp->cooperative->name,
                        'payment_status' => $monthStatus,
                        'loan_amount' => $totalLoan,
                        'amount_paid' => $totalPaid,
                        'remaining_balance' => $remaining,
                        'last_paid' => $lastPaidDate,
                        'penalty' => $totalPenalty,
                    ];
                } else {
                    if (! in_array($cp->program_status, ['Resolved', 'Completed'])) {
                        $checklistOnly[] = [
                            'cooperative_name' => $cp->cooperative->name,
                            'program_status' => $cp->program_status,
                        ];
                    }
                }
            }

            return [
                'program_name' => $program->name ?? 'N/A',
                'has_amortization' => $hasAmortization,
                'checklist_only' => $checklistOnly,
            ];
        });

        $pdf = Pdf::loadView('monthly', [
            'selectedProgram' => $selectedProgramName,
            'date' => $selectedMonth->format('F Y'),
            'registeredCoops' => $registeredCoops,
            'finishedCoops' => $finishedCoops,
            'programs' => $programs,
        ])->setPaper('a4', 'portrait');

        if ($request->has('download')) {
            return $pdf->download($selectedProgramFileName.'_Monthly_Report_'.$selectedMonth->format('F_Y').'.pdf');
        }

        return $pdf->stream($selectedProgramFileName.'_Monthly_Report_'.$selectedMonth->format('F_Y').'.pdf');
    }

    public function archive($id)
    {
        $program = Programs::findOrFail($id);
        $program->archive = true;
        $program->save();
        return back()->with('success', 'Program archived successfully!');
    }
    public function unarchive($id)
    {
        $program = Programs::findOrFail($id);
        $program->archive = false;
        $program->save();

        return back()->with('success', 'Program unarchived successfully!');
    }

    public function storeChecklist(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $checklist = Checklists::create($data);

        return redirect()->back()->with('success', 'Checklist added');
    }

    public function updateChecklist(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $checklist = Checklists::findOrFail($id);
        $checklist->update($data);

            return redirect()->back()->with('success', 'Checklist updated');
    }

    public function destroyChecklist($id)
    {
        $checklist = Checklists::findOrFail($id);
        $checklist->delete();

        return redirect()->back()->with('success', 'Checklist deleted successfully');
    }
}
