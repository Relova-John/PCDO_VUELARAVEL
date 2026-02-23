<?php

namespace App\Notifications;

use App\Models\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanOverdueNotification extends Notification
{
    use Queueable;

    protected $schedule;

    public function __construct($schedule)
    {
        $this->schedule = $schedule;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $schedule = $this->schedule;
        $coopProgram = $schedule->coopProgram;
        $datereleased = $coopProgram->start_date->format('F d, Y');
        $maturitydate = $coopProgram->end_date->format('F d, Y');
        $monthlyDue = $schedule->current_balance;
        $totalpaid = $schedule->coopProgram?->amortizationSchedules()
            ->whereNotNull('amount_paid')
            ->sum('amount_paid');
        $lastPayment = $schedule->coopProgram?->amortizationSchedules()
            ->whereNotNull('date_paid')
            ->orderByDesc('date_paid')
            ->first();
        $lastPaymentDate = $lastPayment?->date_paid?->format('F d, Y') ?? 'N/A';
        $lastamountPaid = $lastPayment?->date_paid ? $schedule->coopProgram?->amortizationSchedules()
            ->whereDate('date_paid', $lastPayment->date_paid)
            ->sum('amount_paid')
        : 0;
        $balanceofToday = $schedule->coopProgram->loan_amount - $totalpaid;
        $datetoday = now()->format('F d, Y');

        $type = null;
        // Status text
        if ($schedule->due_date->isToday()) {
            $statusText = 'Your payment is due today.';
            $type = 'due_today';
        } elseif (now()->diffInDays($schedule->due_date, false) == 3) {
            $statusText = 'Your payment is due in 3 day(s).';
            $type = 'due_soon';
        } elseif (now()->diffInDays($schedule->due_date, false) < 0) {
            $days = (int) abs(now()->diffInDays($schedule->due_date, false)); // cast to int
            $statusText = "Your payment is overdue by {$days} day(s).";
            $type = 'overdue';
        } else {
            $days = (int) now()->diffInDays($schedule->due_date); // cast to int
            $statusText = "Your payment is due in {$days} day(s).";
            $type = 'due_in';
        }

        // Store some of the details to the database
        $subject = 'Notice of Payment';
        $body = $coopProgram->program->name.' LOAN: ₱'.number_format($coopProgram->loan_amount ?? 0, 2)."\n".
            'Date Released: '.$datereleased."\n".
            'Maturity date: '.$maturitydate."\n".
            'Monthly Amortization: ₱'.number_format($monthlyDue, 2)."\n".
            'Total Amount to Paid: ₱'.number_format($totalpaid, 2)."\n".
            'Date of Last Payment: '.$lastPaymentDate."\n".
            'Amount Paid: ₱'.number_format($lastamountPaid, 2)."\n".
            'Balance as of '.$datetoday.' : ₱'.number_format($lastamountPaid, 2)."\n";

        // Save to DB
        $pending = $schedule->pendingnotifications()
            ->where('processed', 0)
            ->first();

        if ($pending) {
            // Update existing pending row
            $pending->update([
                'subject' => $subject,
                'body' => $body,
                'processed' => 1,
            ]);
        } else {
            // No pending row → insert directly into notifications
            Notifications::create([
                'schedule_id' => $schedule->id,
                'coop_id' => $coopProgram->coop_id,
                'type' => $type,
                'subject' => $subject,
                'body' => $body,
                'processed' => 1,
            ]);
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notice_of_payment', [
                'coopProgram' => $coopProgram,
                'schedule' => $schedule,
                'statusText' => $statusText,
                'datereleased' => $datereleased,
                'maturitydate' => $maturitydate,
                'monthlyDue' => $monthlyDue,
                'totalpaid' => $totalpaid,
                'lastpaymentDate' => $lastPaymentDate,
                'lastamountPaid' => $lastamountPaid,
                'balanceofToday' => $balanceofToday,
                'datetoday' => $datetoday,
                'message' => $this,
            ]);
    }
}
