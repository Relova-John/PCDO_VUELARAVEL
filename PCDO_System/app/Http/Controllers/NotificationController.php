<?php

namespace App\Http\Controllers;

use App\Models\Notifications;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notifications::with('schedule.coopProgram.cooperative')
            ->latest()
            ->get();

        return Inertia::render('notification/index', [
            'notifications' => $notifications,
        ]);
    }

    public function show($id)
    {
        $notification = Notifications::with('schedule.coopProgram.cooperative')
            ->findOrFail($id);

        return Inertia::render('notification/show', [
            'notification' => $notification,
        ]);
    }

    public function downloadnotice($id)
    {
        $notification = Notifications::with('schedule.coopProgram.cooperative.details.city', 'schedule.coopProgram.cooperative.details.province')
            ->findOrFail($id);

        $schedule = $notification->schedule;
        $coopProgram = $schedule->coopProgram;

        $datereleased = $coopProgram->start_date->format('F d, Y');
        $maturitydate = $coopProgram->end_date->format('F d, Y');
        $monthlyDue = $schedule->installment;
        $totalpaid = $coopProgram->amortizationSchedules()->whereNotNull('amount_paid')->sum('amount_paid');
        $lastPayment = $coopProgram->amortizationSchedules()->whereNotNull('date_paid')->orderByDesc('date_paid')->first();
        $lastPaymentDate = $lastPayment?->date_paid
        ? Carbon::parse($lastPayment->date_paid)->format('F d, Y')
        : 'N/A';
        $lastamountPaid = $lastPayment?->date_paid
            ? $coopProgram->amortizationSchedules()->whereDate('date_paid', $lastPayment->date_paid)->sum('amount_paid')
            : 0;
        $balanceofToday = $coopProgram->loan_amount - $totalpaid;
        $datetoday = now()->format('F d, Y');

        $pdf = Pdf::loadView('emails.notice_of_payment', [
            'coopProgram' => $coopProgram,
            'schedule' => $schedule,
            'datereleased' => $datereleased,
            'maturitydate' => $maturitydate,
            'monthlyDue' => $monthlyDue,
            'totalpaid' => $totalpaid,
            'lastpaymentDate' => $lastPaymentDate,
            'lastamountPaid' => $lastamountPaid,
            'balanceofToday' => $balanceofToday,
            'datetoday' => $datetoday,
            'isPdf' => true,
        ]);

        $fileName = 'Notice_of_Payment_'.$coopProgram->cooperative->name.'_'.$notification->created_at->format('Y-m-d').'.pdf';

        return $pdf->download($fileName);
    }
}
