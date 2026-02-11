<?php

namespace App\Console\Commands;

use App\Models\AmortizationSchedules;
use App\Models\PendingNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDuePayments extends Command
{
    protected $signature = 'payments:check-due';

    protected $description = 'Insert pending notifications for payments before due, due today, and overdue.';

    public function handle()
    {
        $now = Carbon::now();
        $this->info('--- Starting payments:check-due at '.$now.' ---');

        // --- 3 days before due ---
        $beforeDue = AmortizationSchedules::whereNull('date_paid')
            ->whereDate('due_date', Carbon::now()->addDays(3))
            ->whereDoesntHave('pendingNotifications', fn ($q) => $q->where('type', 'before_due'))
            ->get();

        foreach ($beforeDue as $schedule) {
            PendingNotification::create([
                'schedule_id' => $schedule->id,
                'coop_id' => $schedule->coopProgram->coop_id ?? null,
                'type' => 'before_due',
                'created_at' => $now,
            ]);
        }

        // --- due today ---
        $dueToday = AmortizationSchedules::whereNull('date_paid')
            ->whereDate('due_date', Carbon::today())
            ->whereDoesntHave('pendingNotifications', fn ($q) => $q->where('type', 'due_today'))
            ->get();

        foreach ($dueToday as $schedule) {
            PendingNotification::create([
                'schedule_id' => $schedule->id,
                'coop_id' => $schedule->coopProgram->coop_id ?? null,
                'type' => 'due_today',
                'created_at' => $now,
            ]);
        }

        // --- 1 day after due (overdue) ---
        $overdue = AmortizationSchedules::whereNull('date_paid')
            ->whereDate('due_date', Carbon::yesterday())
            ->whereDoesntHave('pendingNotifications', fn ($q) => $q->where('type', 'overdue'))
            ->get();

        foreach ($overdue as $schedule) {
            PendingNotification::create([
                'schedule_id' => $schedule->id,
                'coop_id' => $schedule->coopProgram->coop_id ?? null,
                'type' => 'overdue',
                'created_at' => $now,
            ]);
        }

        $this->info('✅ Pending payment notifications inserted successfully.');
        $this->info('--- Finished payments:check-due at '.now().' ---');
    }
}
