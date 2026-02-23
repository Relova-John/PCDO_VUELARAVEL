<?php

namespace App\Http\Controllers;

use App\Models\AmortizationOld;
use App\Models\AmortizationSchedules;
use App\Models\CoopProgram;
use App\Models\Resolved;
use App\Notifications\LoanOverdueNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class AmortizationScheduleController extends Controller
{
    // Show the Cooperative table
    public function index()
    {
        $loans = CoopProgram::with(['program', 'cooperative', 'amortizationSchedules'])
            ->withCount('amortizationSchedules')
            ->get()
            ->map(fn($p) => [
                'id' => $p->cooperative?->id ?? 'N/A',
                'cooperative_name' => $p->cooperative?->name ?? 'N/A',
                'program_name' => $p->program?->name ?? 'N/A',
                'loan_amount' => $p->loan_amount ?? 0,
                'status' => $p->program_status ?? 'N/A',
                'has_schedule' => $p->amortization_schedules_count > 0,
                'coop_program_id' => $p->id,
                'next_due_date' => optional(
                    $p->amortizationSchedules->where('status', '!=', 'Paid')->sortBy('due_date')->first()
                )->due_date?->format('Y-m-d') ?? 'N/A',
            ]);

        return Inertia::render('payments/index', [
            'coopPrograms' => $loans,
        ]);
    }

    public function amortizationFile($id)
    {
        $coopProgram = CoopProgram::find($id);
        if (! $coopProgram) {
            abort(404, 'Cooperative program not found.');
        }

        $amortization = AmortizationOld::where('coop_program_id', $id)->first();

        if (! $amortization || ! $amortization->file_content) {
            abort(404, 'Amortization schedule not found.');
        }

        $content = $amortization->file_content;

        return $this->pdfResponse($content, $coopProgram, 'Amortization_Schedule');
    }

    private function pdfResponse(string $pdfContent, CoopProgram $coopProgram, string $suffix): Response
    {
        $disposition = request()->boolean('download')
            ? 'attachment'
            : 'inline';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $this->generateFileName($coopProgram, $suffix) . '"')
            ->header('Content-Length', strlen($pdfContent))
            ->header('Cache-Control', 'public, max-age=0, must-revalidate')
            ->header('Accept-Ranges', 'bytes')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function generateFileName(CoopProgram $coopProgram, string $suffix)
    {
        $coopName = $coopProgram->cooperative?->name ?? 'Cooperative';
        $programName = $coopProgram->program?->name ?? 'Program';
        $createdDate = optional($coopProgram->created_at)->format('Y-m-d') ?? date('Y-m-d');

        // Clean and safe filename
        $safeCoop = preg_replace('/[^A-Za-z0-9_\-]/', '_', $coopName);
        $safeProgram = preg_replace('/[^A-Za-z0-9_\-]/', '_', $programName);
        $safeSuffix = preg_replace('/[^A-Za-z0-9_\-]/', '_', $suffix);

        return "{$safeCoop}_{$safeProgram}_{$createdDate}_{$safeSuffix}.pdf";
    }

    // Show the Amortization Schedule
    public function show($coopProgramId)
    {
        $coopProgram = CoopProgram::with('cooperative', 'program', 'amortizationSchedules')
            ->findOrFail($coopProgramId);

        $firstSchedule = $coopProgram->amortizationSchedules()->orderBy('due_date')->first();
        $lastSchedule = $coopProgram->amortizationSchedules()->orderByDesc('due_date')->first();

        $startDate = optional($firstSchedule?->due_date)->format('Y-m-d')
            ?? optional($coopProgram->start_date)->format('Y-m-d')
            ?? now()->format('Y-m-d');

        $gracePeriod = $coopProgram->with_grace ?? 0;
        $termMonths = max(($coopProgram->program?->term_months ?? 0) - $gracePeriod, 0);

        return Inertia::render('payments/amortization', [
            'coopProgram' => [
                'id' => $coopProgram->id,
                'cooperative_name' => $coopProgram->cooperative?->name ?? 'N/A',
                'program_name' => $coopProgram->program?->name ?? 'N/A',
                'loan_amount' => $coopProgram->loan_amount ?? 0,
                'status' => $coopProgram->program_status ?? 'N/A',
                'program_status' => $coopProgram->program_status ?? 'N/A',
                'resolved' => $coopProgram->program_status === 'Resolved',
                'schedules' => $coopProgram->amortizationSchedules->map(fn($s) => [
                    'id' => $s->id,
                    'due_date' => optional($s->due_date)->format('Y-m-d'),
                    'installment' => $s->installment ?? 0,
                    'penalty_amount' => $s->penalty_amount ?? 0,
                    'amount_paid' => $s->amount_paid ?? 0,
                    'balance' => $s->balance ?? $s->current_balance ?? 0,
                    'is_paid' => $s->status === 'Paid',
                    'status' => $s->status ?? 'Unpaid',
                ]),
                'start_date' => $startDate,
                'grace_period' => $gracePeriod,
                'term_months' => $termMonths,
                'expected_end_date' => optional($lastSchedule?->due_date)->format('Y-m-d'),
            ],
        ]);
    }

    // Marks Paid
    public function markPaid(Request $request, AmortizationSchedules $schedule)
    {
        $request->validate([
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg|max:20000',
        ]);

        $binaryImage = file_get_contents($request->file('receipt_image')->getRealPath());

        $due = ($schedule->balance ?? $schedule->current_balance) + $schedule->penalty_amount;

        $schedule->amount_paid = $due;
        $schedule->balance = null;
        $schedule->status = 'Paid';
        $schedule->date_paid = now();
        $schedule->receipt_image = $binaryImage;
        $schedule->save();

        return back()->with('success', 'Payment marked as paid.');
    }
    public function OneTap(Request $request, $coopProgramId)
    {
        $request->validate([
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg|max:20000s',
        ]);

        $binaryImage = file_get_contents($request->file('receipt_image')->getRealPath());

        // Load CoopProgram with schedules
        $coopProgram = CoopProgram::with('amortizationSchedules')->findOrFail($coopProgramId);


        foreach ($coopProgram->amortizationSchedules as $schedule) {
            if (!in_array($schedule->status, ['Paid', 'Resolved'])) {
                $schedule->amount_paid = $schedule->current_balance + ($schedule->balance ?? 0);
                $schedule->balance = 0;
                $schedule->status = 'Paid';
                $schedule->date_paid = now();
                $schedule->receipt_image = $binaryImage;
                $schedule->save();
            }
        }

        return redirect()->back()->with('success', 'All schedules marked as paid successfully.');
    }

    // Sends A Overdue Email
    public function sendOverdueEmail($scheduleId)
    {
        $schedule = AmortizationSchedules::with('coopProgram.cooperative', 'pendingnotifications', 'cooperative')->findOrFail($scheduleId);
        $programEmail = $schedule->coopProgram->cooperative->coopDetail->email ?? null;

        if (! $programEmail) {
            return back()->with('error', 'No email found for this cooperative program.');
        }

        Notification::route('mail', $programEmail)
            ->notify(new LoanOverdueNotification($schedule));

        // Mark related pending notifications as processed
        $schedule->pendingnotifications()
            ->where('type', 'overdue')
            ->update(['processed' => 1]);

        return back()->with('success', 'Overdue email sent to ' . $programEmail);
    }

    // Send all overdue notification
    public function notifyAllOverdue()
    {
        // Get all schedules that are overdue and not paid/resolved
        $overdueSchedules = AmortizationSchedules::whereNotIn('status', ['Paid', 'Resolved'])
            ->where('due_date', '<', now())
            ->get();

        // Group schedules by coop_program
        $groupedByCoop = $overdueSchedules->groupBy('coop_program_id');

        $report = [];

        foreach ($groupedByCoop as $coopProgramId => $schedules) {
            $coopProgram = CoopProgram::find($coopProgramId);
            $Email = $coopProgram->cooperative->coopDetail->email;

            if (!$Email) {
                $report[] = "CoopProgram ID {$coopProgramId} not found. Skipped.";
                continue;
            }

            $coopName = $coopProgram->cooperative->name ?? 'Cooperative';

            // Collect emails to notify
            $Email = $coopProgram->cooperative->coopDetail->email;

            if (empty($Email)) {
                $report[] = "No emails found for {$coopName}. Skipped.";
                continue;
            }

            // Build email content
            $scheduleList = $schedules->map(function ($s) {
                return "- Due: " . $s->due_date->format('M d, Y') .
                    " | Amount: ₱" . number_format($s->current_balance + ($s->balance ?? 0));
            })->implode("\n");

            $message = "Dear {$coopName},\n\nThe following schedules are overdue:\n{$scheduleList}\n\nPlease settle immediately.\n\nThanks.";

            try {
                // Send email
                Mail::raw($message, function ($mail) use ($Email) {
                    $mail->to($Email)
                        ->subject('Overdue Payment Notification');
                });

                $report[] = "Notification sent to {$coopName} ({implode(', ', $Email)}).";
            } catch (\Exception $e) {
                $report[] = "Failed to send to {$coopName}: " . $e->getMessage();
            }
        }

        return back()->with('success', 'Overdue notifications processed. Check log for details.');
    }

    // Remove penalty from a schedule
    public function penalty(Request $request, AmortizationSchedules $schedule)
    {
        if ($request->has('remove')) {

            $validated = $request->validate([
                'remarks' => 'required|string|max:255',
            ]);

            $penaltyToRemove = $schedule->penalty_amount;

            if ($penaltyToRemove <= 0) {
                return back()->with('error', 'No penalty to remove.');
            }

            AmortizationSchedules::where('coop_program_id', $schedule->coop_program_id)
                ->whereDate('due_date', '>', $schedule->due_date)
                ->whereDate('due_date', '<=', now())
                ->orderBy('due_date', 'asc')
                ->get()
                ->each(function ($futureSchedule) use ($penaltyToRemove) {
                    $futureSchedule->current_balance -= $penaltyToRemove;
                    $futureSchedule->save();
                });

            $schedule->update([
                'penalty_amount' => 0,
                'notes' => $validated['remarks'],
            ]);

            return back()->with('success', 'Penalty removed from current and all future balances successfully.');
        }

        return back()->with('error', 'Invalid action.');
    }

    // Note a payment amount (partial or full) for a schedule.
    public function notePayment(Request $request, $id)
    {
        $schedule = AmortizationSchedules::findOrFail($id);

        $request->validate([
            'amount_paid' => 'required|numeric|min:0',
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg|max:20000',

        ]);

        $binaryImage = file_get_contents($request->file('receipt_image')->getRealPath());
        $remaining = $request->amount_paid;

        // Get all schedules for this coop program ordered by due date
        $schedules = AmortizationSchedules::where('coop_program_id', $schedule->coop_program_id)
            ->where('id', '>=', $schedule->id)
            ->get();

        // Reads everything and loops
        foreach ($schedules as $sch) {

            if ($remaining <= 0)
                break;

            // Calculate effective due considering any previous balance
            $effectiveDue = $sch->current_balance + ($sch->balance ?? 0);

            $toPay = min($remaining, $effectiveDue);

            $sch->amount_paid += $toPay;

            $newBalance = $effectiveDue - $toPay;

            $sch->balance = $newBalance > 0 ? $newBalance : null;
            $sch->status = $newBalance > 0 ? 'Partial Paid' : 'Paid';
            $sch->date_paid = now();
            $sch->receipt_image = $binaryImage;
            $sch->save();

            $remaining -= $toPay;

            // ✅ Update next schedule's current_balance with leftover balance
            $nextSchedule = AmortizationSchedules::where('coop_program_id', $sch->coop_program_id)
                ->where('id', '>', $sch->id)
                ->orderBy('id', 'asc')
                ->first();

            if ($nextSchedule && $newBalance > 0) {
                $nextSchedule->current_balance += $newBalance;
                $nextSchedule->save();
            }
        }

        $lastSchedule = AmortizationSchedules::where('coop_program_id', $schedule->coop_program_id)
            ->orderByDesc('due_date')
            ->first();

        if ($lastSchedule && $lastSchedule->balance > 0) {
            $nextDueDate = Carbon::parse($lastSchedule->due_date)->addMonth();
            $carryOver = $lastSchedule->balance;



            $newSchedule = AmortizationSchedules::create([
                'coop_program_id' => $lastSchedule->coop_program_id,
                'current_balance' => $carryOver,
                'amount_paid' => 0,
                'balance' => 0,
                'penalty_amount' => 0,
                'installment' => 0,
                'status' => 'Unpaid',
                'due_date' => $nextDueDate,
            ]);


            $lastSchedule->save();

            $lastSchedule = $newSchedule;
        }

        return redirect()->back()->with('success', 'Payment noted successfully.');
    }

    // Marks Incomplete
    public function markIncomplete($id)
    {
        $coopProgram = CoopProgram::findOrFail($id);
        $coopProgram->program_status = null;
        $coopProgram->save();

        return redirect()->back()->with('success', 'Program marked as Incomplete.');
    }

    // Marks Resolved
    public function markResolved(Request $request, $loanId)
    {
        $loan = CoopProgram::with('amortizationSchedules')->findOrFail($loanId);

        // Validate uploaded file
        $request->validate([
            'receipt' => 'required|mimes:jpg,jpeg,png,pdf|max:20000s',
        ]);

        // Directly read the file as binary (no storage or unlink)
        $binaryContent = file_get_contents($request->file('receipt')->getRealPath());

        // Save binary into database
        Resolved::create([
            'coop_program_id' => $loan->id,
            'file_content' => $binaryContent,
        ]);

        // Mark amortization schedules as resolved
        if ($loan->amortizationSchedules->count() > 0) {
            foreach ($loan->amortizationSchedules as $schedule) {
                $schedule->update([
                    'status' => 'Resolved',
                    'date_paid' => now(),
                    'balance' => 0,
                    'penalty_amount' => 0,
                ]);
            }
        }

        // Update main program status
        $loan->update(['program_status' => 'Resolved']);

        return redirect()
            ->back()
            ->with('success', 'Loan marked as resolved successfully!');
    }

    public function downloadAmortizationPdf($coopProgramId)
    {
        $coopProgram = CoopProgram::with([
            'amortizationSchedules',
            'cooperative.details.province',
            'cooperative.details.city',
            'cooperative.members',
            'program',
        ])->findOrFail($coopProgramId);

        // Collect amortization schedules (even unpaid ones)
        $schedules = $coopProgram->amortizationSchedules()
            ->orderBy('due_date', 'asc')
            ->get();

        // Identify cooperative officers
        $chairman = $coopProgram->cooperative->members
            ->where('position', 'Chairman')
            ->first();
        $treasurer = $coopProgram->cooperative->members
            ->where('position', 'Treasurer')
            ->first();
        $manager = $coopProgram->cooperative->members
            ->where('position', 'Manager')
            ->first();

        $chairmanFullName = $chairman
            ? trim("{$chairman->first_name} {$chairman->middle_initial} {$chairman->last_name}")
            : 'N/A';
        $treasurerFullName = $treasurer
            ? trim("{$treasurer->first_name} {$treasurer->middle_initial} {$treasurer->last_name}")
            : 'N/A';
        $managerFullName = $manager
            ? trim("{$manager->first_name} {$manager->middle_initial} {$manager->last_name}")
            : 'N/A';

        // Address and contact info
        $details = $coopProgram->cooperative->details ?? null;
        $province = $details?->province?->name ?? '';
        $city = $details?->city?->name ?? '';
        $address = trim("{$province}, {$city}");
        $contact = $details?->contact_number ?? 'N/A';

        // Prepare data for Blade
        $data = [
            'coop' => $coopProgram->cooperative,
            'coopProgram' => $coopProgram,
            'schedules' => $schedules,
            'address' => $address,
            'contact' => $contact,
            'chairman' => $chairmanFullName,
            'treasurer' => $treasurerFullName,
            'manager' => $managerFullName,
        ];

        // Generate PDF using the same amortization_schedule.blade.php view
        $pdf = Pdf::loadView('amortization_schedule', $data)
            ->setPaper('legal', 'portrait')
            ->setOptions([
                'dpi' => 80,
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Download filename
        $filename = ($coopProgram->cooperative->name ?? 'Cooperative') . '_Amortization_Schedule_' . ($coopProgram->start_date)->format('Y-m-d') . '_' . ($coopProgram->end_date)->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
