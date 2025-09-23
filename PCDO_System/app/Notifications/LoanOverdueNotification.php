<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LoanOverdueNotification extends Notification
{
    use Queueable;

    protected $loan;
    public function __construct($loan)
    {
        $this->loan = $loan;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Find the earliest overdue payment schedule
        $dueSchedule = $this->loan->paymentSchedules()
            ->whereDate('due_date', '<=', now())
            ->orderBy('due_date', 'asc')
            ->first();

        $dueDateText = $dueSchedule
            ? $dueSchedule->due_date->format('F d, Y')
            : 'No overdue schedule found';

        $totalDue = $this->loan->paymentSchedules()
            ->where('is_paid', false)
            ->whereDate('due_date', '<=', now())
            ->get()
            ->sum(function ($schedule) {
                return $schedule->amount_due;
            });

        $penalty = $this->loan->paymentSchedules()
            ->where('is_paid', false)
            ->whereDate('due_date', '<=', now())
            ->get()
            ->sum(function ($schedule) {
                return ($schedule->amount_due * 0.01) + $schedule->amount_due;
            });

        return (new MailMessage)
            ->subject('Loan Payment Overdue')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your loan of ₱' . number_format($this->loan->amount, 2) . ' is overdue.')
            ->line('Due Date: ' . $dueDateText)
            ->line('Amount to pay: ₱' . number_format($totalDue, 2))
            ->line('Amount to pay with penalty: ₱' . number_format($penalty, 2))
            ->line('Please settle your payment as soon as possible to avoid penalties.');
    }

}
